Permet de styler le texte d'espace réservé d'un élément de formulaire.

### Definition

```code
lang: scss
---
// themes/vactory/src/scss/mixins/_placeholder.scss
@mixin input-placeholder {
  &.placeholder {
    @content;
  }

  &:-moz-placeholder {
    @content;
  }

  &::-moz-placeholder {
    @content;
  }

  &:-ms-input-placeholder {
    @content;
  }

  &::-webkit-input-placeholder {
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
.input {
   color: gray;

 @include input-placeholder() {
   color: red;
 }
}
```


```code
lang: html
---
<input type="email" class="input" placeholder="john.doe@void.fr">
```

#### Result

```code
lang: scss
---
.input::-webkit-input-placeholder { /* Chrome/Opera/Safari */
  color: pink;
}
.input::-moz-placeholder { /* Firefox 19+ */
  color: pink;
}
.input:-ms-input-placeholder { /* IE 10+ */
  color: pink;
}
.input:-moz-placeholder { /* Firefox 18- */
  color: pink;
}
```
