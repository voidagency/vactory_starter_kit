Appliqué un ensemble de règles uniquement pour IE.

### Definition

```code
lang: scss
---
// themes/vactory/src/scss/mixins/_ie.scss
@mixin if-ie() {
  @media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {
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
   display: flex;

 @include if-ie() {
   display: block;
 }
}
```


