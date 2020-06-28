Permet de définir des blocs de hauteur égale.

### Definition

```code
lang: scss
---
// themes/vactory/src/scss/mixins/_equal-height.scss
@mixin row-make-equal-height() {
  display: flex !important; // scss-lint:disable ImportantRule
  flex-wrap: wrap;

  @include media-breakpoint-down(sm) {
    display: block;
  }

  > * {
    display: flex;
    height: auto;

    @include if-ie() {
      flex-direction: column;
    }
  }

  &:before {
    display: none;
  }
}
```

### Props

Aucun.


### Examples

```code
lang: scss
---
.articles-list > .row {
 @include row-make-equal-height();
}
```

```code
lang: html
---
<div class="articles-list">
 <div class="row">
   <article class="card">...</article>
   <article class="card">...</article>
   <article class="card">...</article>
 </div>
</div>
```


