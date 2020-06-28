Appliqué un ensemble de règles uniquement dans le mode RTL.

### Definition

```code
lang: scss
---
// themes/vactory/src/scss/mixins/_rtl.scss
@mixin rtl() {
  html[dir=rtl] & {
    @content;
  }
}
```

### Props

Aucun.


### Examples

```code
lang: scss
---
.component {
 margin-left: 1em;

 @include rtl() {
   margin-left: 0;
   margin-right: 1em;
 }
}
```


