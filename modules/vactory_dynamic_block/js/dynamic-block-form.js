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
    if (typeof window !== 'undefined' && !window.tailwindcssIso) {
      try {
        var importFunc = new Function('url', 'return import(url)');
        var tailwindcssIsoModule = await importFunc(modulePath);

        var generateTailwindCSS = 
          tailwindcssIsoModule.generateTailwindCSS ||
          (tailwindcssIsoModule.default && tailwindcssIsoModule.default.generateTailwindCSS) ||
          tailwindcssIsoModule.default ||
          tailwindcssIsoModule;

        if (typeof generateTailwindCSS !== 'function') {
          console.error('Could not find generateTailwindCSS function');
          throw new Error('Could not find generateTailwindCSS function');
        }

        window.tailwindcssIso = {
          generateTailwindCSS: generateTailwindCSS,
          customThemeCSS: customThemeCSS
        };

        console.log('tailwindcss-iso initialized');
      } catch (e) {
        console.error('Failed to import tailwindcss-iso:', e);
        window.tailwindcssIso = {
          generateTailwindCSS: async function() {
            console.error('tailwindcss-iso not available');
            return '';
          },
          customThemeCSS: ''
        };
      }
    }
    return window.tailwindcssIso;
  }

  // Prefix Tailwind CSS custom properties to avoid conflicts
  function prefixTailwindProperties(css, blockId) {
    var shortId = blockId.slice(0, 12).replace(/-+$/, '');
    return css.replace(/--tw-/g, '--jsx-' + shortId + '-');
  }

  // Generate CSS from content using tailwindcss-iso
  async function generateCSS(content, blockId, customThemeCSS, modulePath) {
    var wrappedContent = '<div class="jsx-block-' + blockId + '">' + content + '</div>';
    
    var tailwindcssIso = await loadTailwindCSS(customThemeCSS, modulePath);
    
    if (!tailwindcssIso || typeof tailwindcssIso.generateTailwindCSS !== 'function') {
      console.error('tailwindcss-iso not loaded');
      return '';
    }

    try {
      var css = await tailwindcssIso.generateTailwindCSS({
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
    var trimmed = code.trim();
    return trimmed.startsWith('function ') || 
           trimmed.startsWith('const ') || 
           trimmed.startsWith('let ');
  }

  // Extract component info (name and default props)
  function extractComponentInfo(code) {
    var componentName = 'Component';
    var functionMatch = code.match(/function\s+(\w+)/);
    var constMatch = code.match(/const\s+(\w+)\s*=/);

    if (functionMatch) {
      componentName = functionMatch[1];
    } else if (constMatch) {
      componentName = constMatch[1];
    }

    // Extract props with defaults
    var propsMatch = code.match(/\{\s*([^}]+)\s*\}/);
    var defaultProps = {};

    if (propsMatch) {
      var propsStr = propsMatch[1];
      var propMatches = propsStr.matchAll(/(\w+)\s*=\s*([^,}]+)/g);
      for (var match of propMatches) {
        try {
          defaultProps[match[1]] = new Function('return ' + match[2].trim())();
        } catch (e) {
          defaultProps[match[1]] = match[2].trim();
        }
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

    var transformed = Babel.transform(jsxString, { presets: ['react'] }).code;
    
    // Debug: Log the first 1000 chars of Babel output
    console.log('Babel transformed output (first 1000 chars):', transformed.substring(0, 1000));
    console.log('Babel output length:', transformed.length);

    var mockReact = {
      createElement: function(type, props) {
        var children = Array.prototype.slice.call(arguments, 2);
        return {
          type: type,
          props: props || {},
          children: children.flat().filter(function(c) { 
            return c !== null && c !== undefined; 
          })
        };
      }
    };

    // Pure JSX expression - wrap in return
    var executeInScope = new Function('mockReact', 
      'var React = mockReact; return (' + transformed + ');'
    );
    return executeInScope(mockReact);
  }

  // Store CodeMirror instance
  var contentEditor = null;
  
  // Debounce timer for live preview
  var previewDebounceTimer = null;

  // Get CodeMirror mode based on block type
  function getCodeMirrorMode(type) {
    return type === 'JSX' ? 'jsx' : 'htmlmixed';
  }

  // Render structure tree using React.createElement
  function renderStructure(structure) {
    if (!structure) return null;
    if (typeof structure === 'string') return structure;
    
    var type = structure.type;
    var props = structure.props || {};
    var children = structure.children || [];
    
    var renderedChildren = children.map(function(child, index) {
      if (typeof child === 'string') return child;
      return React.createElement(React.Fragment, { key: index }, renderStructure(child));
    });
    
    return React.createElement(type, props, renderedChildren.length > 0 ? renderedChildren : null);
  }

  // Hydrate interactive component from code
  function createHydratedComponent(hydrateData) {
    try {
      var componentFactory = new Function('React', 
        'var useState = React.useState, useEffect = React.useEffect, useRef = React.useRef, useMemo = React.useMemo, useCallback = React.useCallback;\n' +
        hydrateData.code + '\n' +
        'return ' + hydrateData.componentName + ';'
      );
      var Component = componentFactory(React);
      return React.createElement(Component, hydrateData.props || {});
    } catch (err) {
      console.error('Failed to hydrate component:', err);
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
    var root = container._reactRoot;
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
      var element;
      
      // Check if this is a HYDRATE component
      if (structure.type === 'HYDRATE') {
        element = createHydratedComponent(structure);
      } else {
        element = renderStructure(structure);
      }
      
      // Wrap with CSS and block class
      var wrapper = React.createElement('div', { className: 'jsx-block-' + (blockId || 'preview') }, [
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
      var errorElement = React.createElement('div', {
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
        var form = textarea.closest('form');
        var typeField = form.querySelector('select[name="type"]') || 
                        form.querySelector('select[name="type[0][value]"]') ||
                        form.querySelector('[data-drupal-selector="edit-type"]');
        
        var initialType = typeField ? typeField.value : 'HTML';
        
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
            var newMode = getCodeMirrorMode(this.value);
            contentEditor.setOption('mode', newMode);
            console.log('CodeMirror mode changed to:', newMode);
          });
        }
      });

      once('dynamic-block-form', '.js-dynamic-block-preview', context).forEach(function (previewBtn) {
        var form = previewBtn.closest('form');
        var config = settings.vactoryDynamicBlock || {};
        var blockId = config.blockId || generateBlockId();

        // Set block_id if empty
        var blockIdField = form.querySelector('[name="block_id"]');
        if (blockIdField && !blockIdField.value) {
          blockIdField.value = blockId;
        }

        previewBtn.addEventListener('click', async function(e) {
          e.preventDefault();
          
          // Try multiple selectors for the type field (select element)
          var typeField = form.querySelector('select[name="type"]') || 
                          form.querySelector('select[name="type[0][value]"]') ||
                          form.querySelector('[data-drupal-selector="edit-type"]');
          var cssField = form.querySelector('[name="css"]');
          var jsStructureField = form.querySelector('[name="js_structure"]');
          var previewContainer = document.getElementById('dynamic-block-preview');

          var type = typeField ? typeField.value : 'HTML';
          
          // Get content from CodeMirror if available, otherwise from textarea
          var content = '';
          if (contentEditor) {
            contentEditor.save(); // Sync to textarea
            content = contentEditor.getValue();
          } else {
            var contentField = form.querySelector('[name="content[0][value]"]');
            content = contentField ? contentField.value : '';
          }
          
          console.log('Block type:', type, 'Content length:', content.length);

          // Generate CSS
          var generatedCSS = await generateCSS(content, blockId, config.customThemeCSS || '', config.tailwindIsoPath);
          if (cssField) cssField.value = generatedCSS;

          // Transform JSX if needed
          var jsStructure = '';
          var jsxError = '';
          if (type === 'JSX' && content) {
            try {
              console.log('Transforming JSX...');
              var structure;
              
              // Check if content is a component function
              if (isComponentFunction(content)) {
                // Component function - create HYDRATE structure
                var info = extractComponentInfo(content);
                var transpiled = transpileToCode(content);
                
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
          var previewHTML = '<details open><summary>Generated CSS (' + generatedCSS.length + ' bytes)</summary><pre style="max-height:300px;overflow:auto;background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:4px;font-size:12px;">' + 
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
          var livePreviewContainer = document.getElementById('dynamic-block-live-preview');
          if (livePreviewContainer) {
            var structureToRender = null;
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
