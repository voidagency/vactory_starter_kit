(function (Drupal, drupalSettings, once) {
  'use strict';

  // Counter for generating unique block IDs (safer alternative to Math.random())
  let blockIdCounter = 0;

  // Generate short UUID (12 chars) without using Math.random()
  function generateBlockId() {
    blockIdCounter += 1;
    // Use counter + timestamp + performance.now() to generate unique hex string
    const timestamp = Date.now().toString(16);
    const perf = Math.floor(performance.now() * 1000).toString(16);
    const counter = blockIdCounter.toString(16);
    // Combine and take first 12 characters
    const combined = (timestamp + perf + counter).replaceAll('.', '').slice(0, 12);
    // Pad if needed to ensure 12 characters
    return combined.padEnd(12, '0');
  }

  // Load tailwindcss-iso from local module file
  async function loadTailwindCSS(customThemeCSS, modulePath) {
    if (typeof globalThis !== 'undefined' && !globalThis.tailwindcssIso) {
      try {
        // Use native dynamic import() which is supported in modern browsers
        const tailwindcssIsoModule = await import(modulePath);

        const generateTailwindCSS = 
          tailwindcssIsoModule.generateTailwindCSS ||
          tailwindcssIsoModule.default?.generateTailwindCSS ||
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

  // Remove trailing dashes from string (avoids regex backtracking)
  function removeTrailingDashes(str) {
    let endIndex = str.length;
    // Find last non-dash character from the end
    for (let i = str.length - 1; i >= 0; i--) {
      if (str[i] !== '-') {
        endIndex = i + 1;
        break;
      }
    }
    return str.slice(0, endIndex);
  }

  // Prefix Tailwind CSS custom properties to avoid conflicts
  function prefixTailwindProperties(css, blockId) {
    const shortId = removeTrailingDashes(blockId.slice(0, 12));
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
      // Invalid JSON - log error for debugging and return original string
      if (e instanceof SyntaxError) {
        console.debug('Failed to parse JSON, returning original string:', e.message);
      } else {
        console.debug('Unexpected error parsing JSON:', e);
      }
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

  // Find opening brace position (linear search, no backtracking)
  function findOpeningBrace(code, maxLength) {
    for (let i = 0; i < code.length && i < maxLength; i++) {
      if (code[i] === '{') {
        return i;
      }
    }
    return -1;
  }

  // Find matching closing brace (handle nesting)
  function findClosingBrace(code, startIndex, maxLength) {
    let depth = 1;
    for (let i = startIndex + 1; i < code.length && i < maxLength; i++) {
      if (code[i] === '{') {
        depth++;
      } else if (code[i] === '}') {
        depth--;
        if (depth === 0) {
          return i;
        }
      }
    }
    return -1;
  }

  // Check if character is a quote character
  function isQuoteChar(char) {
    return char === '"' || char === "'" || char === '`';
  }

  // Handle quote state transitions
  function handleQuoteState(char, inQuotes, quoteChar, current) {
    const prev = current.length > 0 ? current.at(-1) : '';
    
    if (!inQuotes && isQuoteChar(char)) {
      return { inQuotes: true, quoteChar: char, addChar: true };
    }
    
    if (inQuotes && char === quoteChar && prev !== '\\') {
      return { inQuotes: false, quoteChar: '', addChar: true };
    }
    
    if (inQuotes) {
      return { inQuotes: true, quoteChar: quoteChar, addChar: true };
    }
    
    return { inQuotes: false, quoteChar: quoteChar, addChar: false };
  }

  // Update nesting depth based on character
  function updateNestingDepth(char, depths) {
    const newDepths = { ...depths };
    
    if (char === '[') newDepths.bracketDepth++;
    else if (char === ']') newDepths.bracketDepth--;
    else if (char === '{') newDepths.braceDepth++;
    else if (char === '}') newDepths.braceDepth--;
    else if (char === '(') newDepths.parenDepth++;
    else if (char === ')') newDepths.parenDepth--;
    
    return newDepths;
  }

  // Check if comma is a valid separator (not in nested structure)
  function isCommaSeparator(char, depths) {
    return char === ',' && 
           depths.bracketDepth === 0 && 
           depths.braceDepth === 0 && 
           depths.parenDepth === 0;
  }

  // Split props string into parts, handling commas in nested structures
  function splitPropsString(propsContent, maxLength) {
    const parts = [];
    let current = '';
    let inQuotes = false;
    let quoteChar = '';
    let depths = { bracketDepth: 0, braceDepth: 0, parenDepth: 0 };
    
    for (const char of propsContent) {
      if (parts.length >= 100) break;
      
      // Handle quoted strings
      const quoteState = handleQuoteState(char, inQuotes, quoteChar, current);
      inQuotes = quoteState.inQuotes;
      quoteChar = quoteState.quoteChar;
      
      if (quoteState.addChar) {
        current += char;
        continue;
      }
      
      // Track brackets/braces/parens
      depths = updateNestingDepth(char, depths);
      
      // Comma is separator only if not in nested structure
      if (isCommaSeparator(char, depths)) {
        const trimmed = current.trim();
        if (trimmed) {
          parts.push(trimmed);
        }
        current = '';
      } else {
        current += char;
      }
    }
    
    // Add last part
    const lastTrimmed = current.trim();
    if (lastTrimmed) {
      parts.push(lastTrimmed);
    }
    
    return parts;
  }

  // Find equals sign position in prop part (not in quotes)
  function findEqualsSign(part) {
    let inQuotes = false;
    let quoteChar = '';
    let prev = '';
    
    for (let i = 0; i < part.length; i++) {
      const char = part[i];
      
      if (!inQuotes && (char === '"' || char === "'" || char === '`')) {
        inQuotes = true;
        quoteChar = char;
      } else if (inQuotes && char === quoteChar && prev !== '\\') {
        inQuotes = false;
        quoteChar = '';
      } else if (!inQuotes && char === '=') {
        return i;
      }
      
      prev = char;
    }
    
    return -1;
  }

  // Validate prop name (alphanumeric + underscore only)
  function isValidPropName(name) {
    if (name.length === 0 || name.length > 100) {
      return false;
    }
    
    for (const char of name) {
      if (!((char >= 'a' && char <= 'z') || (char >= 'A' && char <= 'Z') || 
            (char >= '0' && char <= '9') || char === '_')) {
        return false;
      }
    }
    
    return true;
  }

  // Extract prop name and value from part string
  function extractPropNameValue(part, eqPos) {
    const propName = part.slice(0, eqPos).trim();
    const propValue = part.slice(eqPos + 1).trim();
    return { propName, propValue };
  }

  // Extract props from code safely without regex backtracking (prevents ReDoS)
  function extractPropsSafely(code) {
    const defaultProps = {};
    const MAX_LENGTH = 2000;
    
    // Limit input length to prevent DoS
    if (code.length > MAX_LENGTH) {
      return defaultProps;
    }
    
    // Find braces
    const braceStart = findOpeningBrace(code, MAX_LENGTH);
    if (braceStart === -1) {
      return defaultProps;
    }
    
    const braceEnd = findClosingBrace(code, braceStart, MAX_LENGTH);
    if (braceEnd === -1) {
      return defaultProps;
    }
    
    // Extract content between braces
    const propsContent = code.slice(braceStart + 1, braceEnd).trim();
    if (!propsContent || propsContent.length > MAX_LENGTH) {
      return defaultProps;
    }
    
    // Split into prop parts
    const parts = splitPropsString(propsContent, MAX_LENGTH);
    
    // Process each prop part
    for (const part of parts) {
      if (!part) continue;
      
      const eqPos = findEqualsSign(part);
      if (eqPos === -1) continue;
      
      const { propName, propValue } = extractPropNameValue(part, eqPos);
      
      // Validate and add prop
      if (isValidPropName(propName) && propValue.length <= MAX_LENGTH) {
        defaultProps[propName] = parsePropValue(propValue);
      }
    }
    
    return defaultProps;
  }

  // Extract component info (name and default props)
  function extractComponentInfo(code) {
    // Limit code length to prevent DoS
    if (code.length > 5000) {
      return { componentName: 'Component', defaultProps: {} };
    }
    
    let componentName = 'Component';
    const functionMatch = code.match(/function\s+(\w+)/);
    const constMatch = code.match(/const\s+(\w+)\s*=/);

    if (functionMatch) {
      componentName = functionMatch[1];
    } else if (constMatch) {
      componentName = constMatch[1];
    }

    // Extract props safely without regex backtracking
    const defaultProps = extractPropsSafely(code);

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

  // Generate a unique key for React element based on its content
  function generateReactKey(child, index) {
    if (typeof child === 'string') {
      return 'str_' + index + '_' + child.slice(0, 20).replaceAll(/[^a-zA-Z0-9]/g, '_');
    }
    if (child && typeof child === 'object') {
      const type = child.type || 'unknown';
      const key = child.props?.key || child.props?.id || child.props?.className;
      if (key) {
        return type + '_' + key;
      }
      // Generate key from type and props
      const propsStr = JSON.stringify(child.props || {}).slice(0, 50);
      return type + '_' + index + '_' + propsStr.replaceAll(/[^a-zA-Z0-9]/g, '_');
    }
    return 'child_' + index;
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
      const key = generateReactKey(child, index);
      return React.createElement(React.Fragment, { key: key }, renderStructure(child));
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

  // Get or create React root for container
  function getReactRoot(container) {
    let root = container._reactRoot;
    if (!root && ReactDOM.createRoot) {
      root = ReactDOM.createRoot(container);
      container._reactRoot = root;
    }
    return root;
  }

  // Render empty state message
  function renderEmptyState(root, container) {
    const message = 'Enter content and click Preview to see the result';
    const emptyElement = React.createElement('div', { 
      style: { color: '#666', textAlign: 'center', padding: '2rem' } 
    }, message);
    
    if (root) {
      root.render(emptyElement);
    } else {
      container.innerHTML = '<div style="color:#666;text-align:center;padding:2rem;">' + message + '</div>';
    }
  }

  // Create preview wrapper element with CSS
  function createPreviewWrapper(element, css, blockId) {
    return React.createElement('div', { className: 'jsx-block-' + (blockId || 'preview') }, [
      css ? React.createElement('style', { key: 'css', dangerouslySetInnerHTML: { __html: css } }) : null,
      React.createElement('div', { key: 'content' }, element)
    ]);
  }

  // Render error element
  function renderErrorElement(err, root, container) {
    const errorMessage = err.message || 'Unknown error';
    const errorElement = React.createElement('div', {
      style: { padding: '1rem', background: '#fee2e2', borderRadius: '0.5rem', color: '#991b1b' }
    }, 'Preview Error: ' + errorMessage);
    
    if (root) {
      root.render(errorElement);
    } else {
      container.innerHTML = '<div style="padding:1rem;background:#fee2e2;border-radius:0.5rem;color:#991b1b;">Preview Error: ' + Drupal.checkPlain(errorMessage) + '</div>';
    }
  }

  // Render live preview using React
  function renderLivePreview(structure, css, blockId, container) {
    if (!container) return;
    
    const root = getReactRoot(container);
    
    if (!structure) {
      renderEmptyState(root, container);
      return;
    }

    try {
      const element = structure.type === 'HYDRATE' 
        ? createHydratedComponent(structure)
        : renderStructure(structure);
      
      const wrapper = createPreviewWrapper(element, css, blockId);
      
      if (root) {
        root.render(wrapper);
      } else {
        ReactDOM.render(wrapper, container);
      }
      
      console.log('Live preview rendered');
    } catch (err) {
      console.error('Error rendering live preview:', err);
      renderErrorElement(err, root, container);
    }
  }

  // Get form field values
  function getFormFields(form) {
    const typeField = form.querySelector('select[name="type"]') || 
                    form.querySelector('select[name="type[0][value]"]') ||
                    form.querySelector('[data-drupal-selector="edit-type"]');
    return {
      typeField: typeField,
      cssField: form.querySelector('[name="css"]'),
      jsStructureField: form.querySelector('[name="js_structure"]'),
      type: typeField ? typeField.value : 'HTML'
    };
  }

  // Get content from editor or textarea
  function getContent(form) {
    if (contentEditor) {
      contentEditor.save();
      return contentEditor.getValue();
    }
    const contentField = form.querySelector('[name="content[0][value]"]');
    return contentField ? contentField.value : '';
  }

  // Transform JSX content to structure
  function transformJSXContent(content) {
    let jsStructure = '';
    let jsxError = '';
    
    try {
      console.log('Transforming JSX...');
      let structure;
      
      if (isComponentFunction(content)) {
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
        structure = transformJSXToStructure(content);
      }
      
      jsStructure = JSON.stringify(structure, null, 2);
      console.log('JSX transformed successfully');
    } catch (err) {
      console.error('JSX transform error:', err);
      jsxError = err.message || 'Unknown error';
    }
    
    return { jsStructure, jsxError };
  }

  // Build preview HTML content
  function buildPreviewHTML(generatedCSS, type, jsStructure, jsxError) {
    const cssDetails = '<details open><summary>Generated CSS (' + generatedCSS.length + ' bytes)</summary><pre style="max-height:300px;overflow:auto;background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:4px;font-size:12px;">' + 
      Drupal.checkPlain(generatedCSS) + '</pre></details>';
    
    if (type !== 'JSX') {
      return cssDetails;
    }
    
    if (jsStructure) {
      return cssDetails + '<details open><summary>JSX Structure</summary><pre style="max-height:300px;overflow:auto;background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:4px;font-size:12px;">' + 
        Drupal.checkPlain(jsStructure) + '</pre></details>';
    }
    
    if (jsxError) {
      return cssDetails + '<details open><summary>JSX Error</summary><pre style="max-height:300px;overflow:auto;background:#4a1515;color:#ff6b6b;padding:1rem;border-radius:4px;font-size:12px;">' + 
        Drupal.checkPlain(jsxError) + '</pre></details>';
    }
    
    return cssDetails;
  }

  // Render live preview content
  function renderLivePreviewContent(livePreviewContainer, type, content, jsStructure, jsxError, generatedCSS, blockId) {
    if (type === 'HTML' && content) {
      livePreviewContainer.innerHTML = '<style>' + generatedCSS + '</style><div class="jsx-block-' + blockId + '">' + content + '</div>';
      return;
    }
    
    if (type === 'JSX' && jsStructure && !jsxError) {
      try {
        const structureToRender = JSON.parse(jsStructure);
        renderLivePreview(structureToRender, generatedCSS, blockId, livePreviewContainer);
      } catch (e) {
        console.error('Failed to parse JSX structure:', e);
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
          
          const fields = getFormFields(form);
          const content = getContent(form);
          
          console.log('Block type:', fields.type, 'Content length:', content.length);

          // Generate CSS
          const generatedCSS = await generateCSS(content, blockId, config.customThemeCSS || '', config.tailwindIsoPath);
          if (fields.cssField) fields.cssField.value = generatedCSS;

          // Transform JSX if needed
          let jsStructure = '';
          let jsxError = '';
          if (fields.type === 'JSX' && content) {
            const transformResult = transformJSXContent(content);
            jsStructure = transformResult.jsStructure;
            jsxError = transformResult.jsxError;
            if (fields.jsStructureField && jsStructure) {
              fields.jsStructureField.value = jsStructure;
            }
          }

          // Display code preview
          const previewContainer = document.getElementById('dynamic-block-preview');
          previewContainer.innerHTML = buildPreviewHTML(generatedCSS, fields.type, jsStructure, jsxError);
          
          // Render live preview
          const livePreviewContainer = document.getElementById('dynamic-block-live-preview');
          if (livePreviewContainer) {
            renderLivePreviewContent(livePreviewContainer, fields.type, content, jsStructure, jsxError, generatedCSS, blockId);
          }
        });
      });
    }
  };

})(Drupal, drupalSettings, once);
