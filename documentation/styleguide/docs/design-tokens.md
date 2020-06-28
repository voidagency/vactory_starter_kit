# Colors

Les jetons sont les atomes visuelle du système de conception - en particulier, ils sont des entités nommées qui stockent des attributs de conception visuelle. Nous les utilisons à la place de valeurs codées en dur (telles que des valeurs hexadécimales pour la couleur ou des valeurs de pixel pour l'espacement) afin de maintenir un système visuel évolutif et cohérent pour le développement de l'interface utilisateur.


## Brand colors
```color
span: 2
name: "$primary"
value: "#2196f3"
```

```color
span: 1
name: "$success"
value: "#2cdd9b"
```

```color
span: 1
name: "$warning"
value: "#fec500"
```

```color
span: 1
name: "$danger"
value: "#ff4d7e"
```

```color
span: 1
name: "$info"
value: "#316ce8"
```

## Gray colors
```color-palette
colors:
  - {name: "$gray-base", value: "#000000"}
  - {name: "$gray-darker", value: "#222222"}
  - {name: "$gray-dark", value: "#333333"}
  - {name: "$gray", value: "#4a4a4a"}
  - {name: "$gray-light", value: "#cfcfcf"}
  - {name: "$gray-lighter", value: "#eeeeee"}
```

# Typography

### Headings
```html|span-12,no-source,plain,light
    <div style='opacity: 0.4;'>h1 (36px)</div>
    <h1>The quick brown fox jumps over the lazy dog</h1>
    <div style='opacity: 0.4;'>h2 (30px)</div>
    <h2>The quick brown fox jumps over the lazy dog</h2>
    <div style='opacity: 0.4;'>h3 (24px)</div>
    <h3>The quick brown fox jumps over the lazy dog</h3>
    <div style='opacity: 0.4;'>h4 (21px)</div>
    <h4>The quick brown fox jumps over the lazy dog</h4>
    <div style='opacity: 0.4;'>h5 (18px)</div>
    <h5>The quick brown fox jumps over the lazy dog</h5>
    <div style='opacity: 0.4;'>h6 (16px)</div>
    <h6>The quick brown fox jumps over the lazy dog</h6>
```

### Paragraph
```html|span-12,no-source,plain,light
  <div style='opacity: 0.4;'>Paragraph (16px/24px)</div>
  <p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.</p>
  <p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.</p>
  <p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.</p>
```

### Lists
```html|span-3,no-source,plain,light
<ul>
    <li>Aliquam molestie quam in tincidunt</li>
    <li>Morbi quis neque non nisl egestas</li>
    <li>Vestibulum nisi nibh, pulvinar sit amet</li>
    <li>Mauris pretium elit ac facilisis mollis</li>
    <li>Mauris vitae magna in dolor porta</li>
</ul>
```
```html|span-3,no-source,plain,light
<ol>
    <li>Aliquam molestie quam in tincidunt</li>
    <li>Morbi quis neque non nisl egestas</li>
    <li>Vestibulum nisi nibh, pulvinar amet</li>
    <li>Mauris pretium elit ac facilisis mollis</li>
    <li>Mauris vitae magna in dolor porta</li>
</ol>
```

### Text Colors
`.text-primary` `.text-muted` `.text-success` `.text-info` `.text-warning` `.text-danger`
```html|span-6,no-source,plain,light
  <p class="text-primary mb-0"><strong>Primary</strong>. Morbi quis neque non nisl egestas laoreet</p>
  <p class="text-muted mb-0"><strong>Muted</strong>. Aliquam molestie quam in tincidunt</p>
  <p class="text-success mb-0"><strong>Success</strong>. Vestibulum nisi nibh, pulvinar sit amet lacinia</p>
  <p class="text-info mb-0"><strong>Info</strong>. Mauris pretium elit ac facilisis mollis posuere</p>
  <p class="text-warning mb-0"><strong>Warning</strong>. Mauris vitae magna in dolor porta aliquam</p>
  <p class="text-danger mb-0"><strong>Danger</strong>. Mauris vitae magna in dolor porta aliquam</p>
```  
### Text Alignments
`.text-left` `.text-center` `.text-right`
```html|span-6,no-source,plain,light
  <p class="text-left"><strong>Text left</strong>. Morbi quis neque non nisl egestas laoreet</p>
  <p class="text-center"><strong>Text center</strong>. Aliquam molestie quam in tincidunt</p>
  <p class="text-right"><strong>Text right</strong>. Vestibulum nisi nibh, pulvinar sit amet lacinia</p>
```  