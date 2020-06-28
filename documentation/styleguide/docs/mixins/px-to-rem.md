un mixin pour convertire du pixle vers rem

### Definition

```code
lang: scss
---
// themes/vactory/src/scss/mixins/_px-to-rem.scss

// Baseline, measured in pixels
// The value should be the same as the font-size value for the html element
// the default value is 16px;

$baseline-px: 16px;

@mixin rem($property, $px-values) {

  // Convert the baseline into rems
  $baseline-rem: $baseline-px / 1rem * 1;

  // Print the first line in pixel values
  #{$property}: $px-values;

  // If there is only one (numeric) value, return the property/value line for it.
  @if type-of($px-values) == "number" {

    #{$property}: $px-values / $baseline-rem;

  } @else {

    // Create an empty list that we can dump values into
    $rem-values: ();

    @each $value in $px-values {

      // If the value is zero or not a number, return it
      @if $value == 0 or type-of($value) != "number" {

        $rem-values: append($rem-values, $value);
      } @else {

        $rem-values: append($rem-values, $value / $baseline-rem);

      }
    }

    // Return the property and its list of converted values
    #{$property}: $rem-values;

  }
}

```

### Props
Aucun.

### Examples

```code
lang: scss
---
.article-card {
  @include rem(padding-bottom, 30px);
  @include rem(margin, 15px 10px 0 20px);
}
```


