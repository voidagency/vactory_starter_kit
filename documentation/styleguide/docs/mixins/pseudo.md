When using ::before and ::after you'll always need these three, so we're saving two lines of code every time you use this. See below an example of using it without any arguments

### Definition

```code
lang: scss
---
// themes/vactory/src/scss/mixins/_pseudo.scss
@mixin pseudo($display: block, $pos: absolute, $content: "") {
  content: $content;
  display: $display;
  position: $pos;
}
```


### Props

- `display` — specifies the type of box used for an HTML element.
- `pos` — the type of positioning method used for an element (static, relative, absolute or fixed).
- `content` — the insert generated content used with the :before and :after pseudo-elements.


### Examples

```code
lang: scss
---
div::after {
 @include pseudo;
 top: -1rem;
 left: -1rem;
 width: 1rem;
 height: 1rem;
}
```


