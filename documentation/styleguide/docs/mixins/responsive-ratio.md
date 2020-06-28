Permet d'ajuster et de maintenir le ratio d'un tag

### Definition

```code
lang: scss
---
// themes/vactory/src/scss/mixins/_responsive-ratio.scss
@import "pseudo";

@mixin responsive-ratio($x, $y, $pseudo: false) {
  $padding: unquote(($y / $x) * 100 + "%");
  @if $pseudo {
    &:before {
      @include pseudo($pos: relative);
      width: 100%;
      padding-top: $padding;
    }
  } @else {
    padding-top: $padding;
  }
}


```

### Props

- `x` — Ratio width.
- `y` — Ratio height.
- `pseudo` — Use pseudo element instead.


### Examples

```code
lang: scss
---
.div {
 @include responsive-ratio(16,9);
}
```


