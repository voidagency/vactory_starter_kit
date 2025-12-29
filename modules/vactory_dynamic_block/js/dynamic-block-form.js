(function (Drupal, drupalSettings, once) {
  'use strict';

  // Generate short UUID (12 chars)
  function generateBlockId() {
    return 'xxxxxxxxxxxx'.replace(/x/g, function() {
      return Math.floor(Math.random() * 16).toString(16);
    });
  }

  // Load tailwindcss-iso from local module file
  async function loadTailwindCSS(customThemeCSS, modulePath) {
    if (typeof globalThis !== 'undefined' && !globalThis.tailwindcssIso) {
      try {
        // Use native dynamic import() which is supported in modern browsers
        const tailwindcssIsoModule = await import(modulePath);

        const generateTailwindCSS = 
          tailwindcssIsoModule.generateTailwindCSS ||
          (tailwindcssIsoModule.default && tailwindcssIsoModule.default.generateTailwindCSS) ||
          tailwindcssIsoModule.default ||
          tailwindcssIsoModule;

        if (typeof generateTailwindCSS !== 'function') {
          console.error('Could not find generateTailwindCSS function');
          throw new Error('Could not find generateTailwindCSS function');
        }

        globalThis.tailwindcssIso = {
          generateTailwindCSS: generateTailwindCSS,
          customThemeCSS: customThemeCSS
        };

        console.log('tailwindcss-iso initialized');
      } catch (e) {
        console.error('Failed to import tailwindcss-iso:', e);
        globalThis.tailwindcssIso = {
          generateTailwindCSS: async function() {
            console.error('tailwindcss-iso not available');
            return '';
          },
          customThemeCSS: ''
        };
      }
    }
    return globalThis.tailwindcssIso;
  }

  // Prefix Tailwind CSS custom properties to avoid conflicts
  function prefixTailwindProperties(css, blockId) {
    const shortId = blockId.slice(0, 12).replace(/-+$/, '');
    return css.replaceAll('--tw-', '--jsx-' + shortId + '-');
  }

  // Generate CSS from content using tailwindcss-iso
  async function generateCSS(content, blockId, customThemeCSS, modulePath) {
    const wrappedContent = '<div class="jsx-block-' + blockId + '">' + content + '</div>';
    
    const tailwindcssIso = await loadTailwindCSS(customThemeCSS, modulePath);
    
    if (!tailwindcssIso || typeof tailwindcssIso.generateTailwindCSS !== 'function') {
      console.error('tailwindcss-iso not loaded');
      return '';
    }

    try {
      let css = await tailwindcssIso.generateTailwindCSS({
        content: wrappedContent,
        css: customThemeCSS,
        importCSS: '@import "tailwindcss/theme.css" important;\n@scope (.jsx-block-' + blockId + ') {\n@import "tailwindcss/utilities.css" important;\n}'
      });

      if (css && css.length > 0) {
        css = prefixTailwindProperties(css, blockId);
        console.log('Generated ' + css.length + ' bytes of CSS');
      }
      return css || '';
    } catch (e) {
      console.error('Error generating CSS:', e);
      return '';
    }
  }

  // Check if code is a component function
  function isComponentFunction(code) {
    const trimmed = code.trim();
    return trimmed.startsWith('function ') || 
           trimmed.startsWith('const ') || 
           trimmed.startsWith('let ');
  }

  // Parse JSON string safely, return original string if parsing fails
  function safeJSONParse(str) {
    try {
      return JSON.parse(str);
    } catch (e) {
      // Invalid JSON, return original string
      return str;
    }
  }

  // Check if string is a quoted literal (single, double, or template)
  function isQuotedString(str) {
    return (str.startsWith('"') && str.endsWith('"')) ||
           (str.startsWith("'") && str.endsWith("'")) ||
           (str.startsWith('`') && str.endsWith('`'));
  }

  // Extract value from quoted string
  function extractQuotedValue(str) {
    return str.slice(1, -1);
  }

  // Parse primitive values (boolean, null, undefined)
  function parsePrimitiveValue(str) {
    if (str === 'true') return true;
    if (str === 'false') return false;
    if (str === 'null') return null;
    if (str === 'undefined') return undefined;
    return null;
  }

  // Parse a simple JavaScript expression value safely
  function parsePropValue(valueStr) {
    const trimmed = valueStr.trim();
    
    // Handle quoted strings (single, double, template literals)
    if (isQuotedString(trimmed)) {
      return extractQuotedValue(trimmed);
    }
    
    // Handle numbers
    if (/^-?\d+(\.\d+)?$/.test(trimmed)) {
      return Number.parseFloat(trimmed);
    }
    
    // Handle primitive values (boolean, null, undefined)
    const primitiveValue = parsePrimitiveValue(trimmed);
    if (primitiveValue !== null) {
      return primitiveValue;
    }
    
    // Handle arrays and objects (JSON)
    if ((trimmed.startsWith('[') && trimmed.endsWith(']')) ||
        (trimmed.startsWith('{') && trimmed.endsWith('}'))) {
      return safeJSONParse(trimmed);
    }
    
    // For complex expressions, return as string
    return trimmed;
  }

  // Extract component info (name and default props)
  function extractComponentInfo(code) {
    let componentName = 'Component';
    const functionMatch = code.match(/function\s+(\w+)/);
    const constMatch = code.match(/const\s+(\w+)\s*=/);

    if (functionMatch) {
      componentName = functionMatch[1];
    } else if (constMatch) {
      componentName = constMatch[1];
    }

    // Extract props with defaults
    // Limit regex match length to prevent ReDoS (max 1000 chars)
    const propsMatch = code.match(/\{\s*([^}]{0,1000})\s*\}/);
    const defaultProps = {};

    if (propsMatch) {
      const propsStr = propsMatch[1];
      // Limit regex match length to prevent ReDoS (max 1000 chars per prop value)
      const propMatches = propsStr.matchAll(/(\w+)\s*=\s*([^,}]{0,1000})/g);
      for (const match of propMatches) {
        defaultProps[match[1]] = parsePropValue(match[2]);
      }
    }

    return { componentName: componentName, defaultProps: defaultProps };
  }

  // Create static fallback for HYDRATE components
  function createStaticFallback(componentName) {
    return {
      type: 'div',
      props: {
        className: 'p-6 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 text-center text-gray-500'
      },
      children: [
        {
          type: 'div',
          props: { className: 'mb-2' },
          children: ['⚡ Loading Interactive Component...']
        },
        {
          type: 'div',
          props: { className: 'text-xs opacity-70' },
          children: [componentName]
        }
      ]
    };
  }

  // Transpile JSX code to JavaScript
  function transpileToCode(jsxCode) {
    if (typeof Babel === 'undefined') {
      throw new Error('Babel is not loaded');
    }
    return Babel.transform(jsxCode, { presets: ['react'] }).code;
  }

  // Transform JSX to structure using Babel (for non-component JSX)
  function transformJSXToStructure(jsxString) {
    if (typeof Babel === 'undefined') {
      throw new Error('Babel is not loaded');
    }

    const transformed = Babel.transform(jsxString, { presets: ['react'] }).code;
    
    // Debug: Log the first 1000 chars of Babel output
    console.log('Babel transformed output (first 1000 chars):', transformed.slice(0, 1000));
    console.log('Babel output length:', transformed.length);

    const mockReact = {
      createElement: function(type, props) {
        const children = Array.prototype.slice.call(arguments, 2);
        return {
          type: type,
          props: props || {},
          children: children.flat().filter(function(c) { 
            return c !== null && c !== undefined; 
          })
        };
      }
    };

    // Execute transformed code using a script element approach
    // This method avoids new Function() and uses DOM script execution
    const tempGlobalKey = '__jsx_result_' + Date.now() + '_' + generateUniqueId();
    let result = null;
    
    try {
      // Create a script element that will execute the transformed code
      const script = document.createElement('script');
      const wrapperCode = '(function(mockReact) { var React = mockReact; return (' + transformed + '); })';
      script.textContent = 'window.' + tempGlobalKey + ' = ' + wrapperCode + '(window.__jsx_mockReact);';
      
      // Store mockReact temporarily in globalThis for script access
      globalThis.__jsx_mockReact = mockReact;
      
      // Append script to body to execute it
      document.body.appendChild(script);
      
      // Retrieve result from global
      result = globalThis[tempGlobalKey];
      
      // Clean up
      script.remove();
      delete globalThis[tempGlobalKey];
      delete globalThis.__jsx_mockReact;
    } catch (e) {
      console.error('Error executing transformed JSX:', e);
      // Clean up on error
      if (globalThis.__jsx_mockReact) {
        delete globalThis.__jsx_mockReact;
      }
      if (globalThis[tempGlobalKey]) {
        delete globalThis[tempGlobalKey];
      }
      throw e;
    }
    
    return result;
  }

  // Store CodeMirror instance
  let contentEditor = null;
  // Counter for generating unique identifiers (safer alternative to Math.random())
  let uniqueIdCounter = 0;

  // Generate a unique alphanumeric identifier (base36, 9 chars) without using Math.random()
  // This maintains the same format as Math.random().toString(36).slice(2, 11) for compatibility
  function generateUniqueId() {
    uniqueIdCounter += 1;
    // Combine Date.now(), performance.now(), and counter to ensure uniqueness
    // Convert to base36 and extract 9 characters to match original format
    const timePart = Date.now().toString(36);
    const perfPart = Math.floor(performance.now() * 1000).toString(36);
    const counterPart = uniqueIdCounter.toString(36);
    // Combine parts and take first 9 characters (matching slice(2, 11) behavior)
    const combined = (timePart + perfPart + counterPart).replaceAll('.', '').slice(0, 9);
    // Pad if needed to ensure 9 characters
    return combined.padEnd(9, '0');
  }

  // Get CodeMirror mode based on block type
  function getCodeMirrorMode(type) {
    return type === 'JSX' ? 'jsx' : 'htmlmixed';
  }

  // Render structure tree using React.createElement
  function renderStructure(structure) {
    if (!structure) return null;
    if (typeof structure === 'string') return structure;
    
    const type = structure.type;
    const props = structure.props || {};
    const children = structure.children || [];
    
    const renderedChildren = children.map(function(child, index) {
      if (typeof child === 'string') return child;
      return React.createElement(React.Fragment, { key: index }, renderStructure(child));
    });
    
    return React.createElement(type, props, renderedChildren.length > 0 ? renderedChildren : null);
  }

  // Hydrate interactive component from code
  function createHydratedComponent(hydrateData) {
    try {
      // Create component using script element approach to avoid new Function/eval
      const tempGlobalKey = '__react_component_' + Date.now() + '_' + generateUniqueId();
      let Component = null;
      
      // Prepare component code with React hooks available
      const componentCode = 'var useState = window.__react_hooks.useState; ' +
                         'var useEffect = window.__react_hooks.useEffect; ' +
                         'var useRef = window.__react_hooks.useRef; ' +
                         'var useMemo = window.__react_hooks.useMemo; ' +
                         'var useCallback = window.__react_hooks.useCallback; ' +
                         hydrateData.code + '\n' +
                         'window.' + tempGlobalKey + ' = ' + hydrateData.componentName + ';';
      
      // Store React hooks in globalThis temporarily
      globalThis.__react_hooks = {
        useState: React.useState,
        useEffect: React.useEffect,
        useRef: React.useRef,
        useMemo: React.useMemo,
        useCallback: React.useCallback
      };
      
      // Create and execute script
      const script = document.createElement('script');
      script.textContent = componentCode;
      document.body.appendChild(script);
      
      // Retrieve component
      Component = globalThis[tempGlobalKey];
      
      // Clean up
      script.remove();
      delete globalThis.__react_hooks;
      delete globalThis[tempGlobalKey];
      
      if (!Component) {
        throw new Error('Component ' + hydrateData.componentName + ' not found');
      }
      
      return React.createElement(Component, hydrateData.props || {});
    } catch (err) {
      console.error('Failed to hydrate component:', err);
      // Clean up on error
      if (globalThis.__react_hooks) {
        delete globalThis.__react_hooks;
      }
      return React.createElement('div', {
        style: { padding: '1.5rem', background: '#fee2e2', border: '2px solid #fca5a5', borderRadius: '0.75rem', color: '#991b1b' }
      }, [
        React.createElement('div', { key: 'title', style: { fontWeight: 'bold', marginBottom: '0.75rem', fontSize: '1.1rem' } }, '⚠️ Component Error'),
        React.createElement('div', { key: 'error', style: { fontSize: '0.875rem', marginBottom: '0.75rem', fontFamily: 'monospace', background: '#fef2f2', padding: '0.75rem', borderRadius: '0.25rem' } }, err.message || 'Failed to load component'),
        React.createElement('div', { key: 'tip', style: { fontSize: '0.75rem', color: '#b91c1c' } }, 'Tip: Make sure your component code is valid.')
      ]);
    }
  }

  // Render live preview using React
  function renderLivePreview(structure, css, blockId, container) {
    if (!container) return;
    
    // Clear previous content
    let root = container._reactRoot;
    if (!root && ReactDOM.createRoot) {
      root = ReactDOM.createRoot(container);
      container._reactRoot = root;
    }
    
    if (!structure) {
      if (root) {
        root.render(React.createElement('div', { 
          style: { color: '#666', textAlign: 'center', padding: '2rem' } 
        }, 'Enter content and click Preview to see the result'));
      } else {
        container.innerHTML = '<div style="color:#666;text-align:center;padding:2rem;">Enter content and click Preview to see the result</div>';
      }
      return;
    }

    try {
      let element;
      
      // Check if this is a HYDRATE component
      if (structure.type === 'HYDRATE') {
        element = createHydratedComponent(structure);
      } else {
        element = renderStructure(structure);
      }
      
      // Wrap with CSS and block class
      const wrapper = React.createElement('div', { className: 'jsx-block-' + (blockId || 'preview') }, [
        css ? React.createElement('style', { key: 'css', dangerouslySetInnerHTML: { __html: css } }) : null,
        React.createElement('div', { key: 'content' }, element)
      ]);
      
      if (root) {
        root.render(wrapper);
      } else {
        // Fallback for older ReactDOM
        ReactDOM.render(wrapper, container);
      }
      
      console.log('Live preview rendered');
    } catch (err) {
      console.error('Error rendering live preview:', err);
      const errorElement = React.createElement('div', {
        style: { padding: '1rem', background: '#fee2e2', borderRadius: '0.5rem', color: '#991b1b' }
      }, 'Preview Error: ' + (err.message || 'Unknown error'));
      
      if (root) {
        root.render(errorElement);
      } else {
        container.innerHTML = '<div style="padding:1rem;background:#fee2e2;border-radius:0.5rem;color:#991b1b;">Preview Error: ' + Drupal.checkPlain(err.message || 'Unknown error') + '</div>';
      }
    }
  }

  Drupal.behaviors.dynamicBlockForm = {
    attach: function (context, settings) {
      // Initialize CodeMirror on content field
      once('codemirror-init', '[name="content[0][value]"]', context).forEach(function (textarea) {
        const form = textarea.closest('form');
        const typeField = form.querySelector('select[name="type"]') || 
                        form.querySelector('select[name="type[0][value]"]') ||
                        form.querySelector('[data-drupal-selector="edit-type"]');
        
        const initialType = typeField ? typeField.value : 'HTML';
        
        // Initialize CodeMirror
        contentEditor = CodeMirror.fromTextArea(textarea, {
          mode: getCodeMirrorMode(initialType),
          theme: 'material-darker',
          lineNumbers: true,
          lineWrapping: true,
          indentUnit: 2,
          tabSize: 2,
          indentWithTabs: false,
          autoCloseBrackets: true,
          matchBrackets: true,
          extraKeys: {
            'Tab': function(cm) {
              cm.replaceSelection('  ', 'end');
            }
          }
        });
        
        // Set editor height
        contentEditor.setSize(null, 400);
        
        console.log('CodeMirror initialized with mode:', getCodeMirrorMode(initialType));
        
        // Change mode when type changes
        if (typeField) {
          typeField.addEventListener('change', function() {
            const newMode = getCodeMirrorMode(this.value);
            contentEditor.setOption('mode', newMode);
            console.log('CodeMirror mode changed to:', newMode);
          });
        }
      });

      once('dynamic-block-form', '.js-dynamic-block-preview', context).forEach(function (previewBtn) {
        const form = previewBtn.closest('form');
        const config = settings.vactoryDynamicBlock || {};
        const blockId = config.blockId || generateBlockId();

        // Set block_id if empty
        const blockIdField = form.querySelector('[name="block_id"]');
        if (blockIdField && !blockIdField.value) {
          blockIdField.value = blockId;
        }

        previewBtn.addEventListener('click', async function(e) {
          e.preventDefault();
          
          // Try multiple selectors for the type field (select element)
          const typeField = form.querySelector('select[name="type"]') || 
                          form.querySelector('select[name="type[0][value]"]') ||
                          form.querySelector('[data-drupal-selector="edit-type"]');
          const cssField = form.querySelector('[name="css"]');
          const jsStructureField = form.querySelector('[name="js_structure"]');
          const previewContainer = document.getElementById('dynamic-block-preview');

          const type = typeField ? typeField.value : 'HTML';
          
          // Get content from CodeMirror if available, otherwise from textarea
          let content = '';
          if (contentEditor) {
            contentEditor.save(); // Sync to textarea
            content = contentEditor.getValue();
          } else {
            const contentField = form.querySelector('[name="content[0][value]"]');
            content = contentField ? contentField.value : '';
          }
          
          console.log('Block type:', type, 'Content length:', content.length);

          // Generate CSS
          const generatedCSS = await generateCSS(content, blockId, config.customThemeCSS || '', config.tailwindIsoPath);
          if (cssField) cssField.value = generatedCSS;

          // Transform JSX if needed
          let jsStructure = '';
          let jsxError = '';
          if (type === 'JSX' && content) {
            try {
              console.log('Transforming JSX...');
              let structure;
              
              // Check if content is a component function
              if (isComponentFunction(content)) {
                // Component function - create HYDRATE structure
                const info = extractComponentInfo(content);
                const transpiled = transpileToCode(content);
                
                console.log('Detected component function:', info.componentName);
                
                structure = {
                  type: 'HYDRATE',
                  componentName: info.componentName,
                  props: info.defaultProps,
                  static: createStaticFallback(info.componentName),
                  code: transpiled
                };
              } else {
                // Pure JSX expression - transform directly
                structure = transformJSXToStructure(content);
              }
              
              jsStructure = JSON.stringify(structure, null, 2);
              if (jsStructureField) jsStructureField.value = jsStructure;
              console.log('JSX transformed successfully');
            } catch (err) {
              console.error('JSX transform error:', err);
              jsxError = err.message || 'Unknown error';
            }
          }

          // Display code preview
          let previewHTML = '<details open><summary>Generated CSS (' + generatedCSS.length + ' bytes)</summary><pre style="max-height:300px;overflow:auto;background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:4px;font-size:12px;">' + 
            Drupal.checkPlain(generatedCSS) + '</pre></details>';
          
          if (type === 'JSX') {
            if (jsStructure) {
              previewHTML += '<details open><summary>JSX Structure</summary><pre style="max-height:300px;overflow:auto;background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:4px;font-size:12px;">' + 
                Drupal.checkPlain(jsStructure) + '</pre></details>';
            } else if (jsxError) {
              previewHTML += '<details open><summary>JSX Error</summary><pre style="max-height:300px;overflow:auto;background:#4a1515;color:#ff6b6b;padding:1rem;border-radius:4px;font-size:12px;">' + 
                Drupal.checkPlain(jsxError) + '</pre></details>';
            }
          }
          
          previewContainer.innerHTML = previewHTML;
          
          // Render live preview
          const livePreviewContainer = document.getElementById('dynamic-block-live-preview');
          if (livePreviewContainer) {
            let structureToRender = null;
            if (type === 'JSX' && jsStructure && !jsxError) {
              try {
                structureToRender = JSON.parse(jsStructure);
              } catch (e) {
                console.error('Failed to parse JSX structure:', e);
              }
            } else if (type === 'HTML' && content) {
              // For HTML, just render the content directly
              livePreviewContainer.innerHTML = '<style>' + generatedCSS + '</style><div class="jsx-block-' + blockId + '">' + content + '</div>';
              return;
            }
            renderLivePreview(structureToRender, generatedCSS, blockId, livePreviewContainer);
          }
        });
      });
    }
  };

})(Drupal, drupalSettings, once);
