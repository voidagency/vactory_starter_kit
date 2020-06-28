# Introduction

Things start getting more interesting and tangible when we start combining elements together.
Components are groups of elements bonded together and are the smallest fundamental units of a compound.
These components take on their own properties and serve as the backbone of our design systems.

Use the following structure:

```code
lang: css
---
.block {
// CSS declarations for .block

 &__element {
   // CSS declarations for .block__element
 }

 &--modifier {
   // CSS declarations for .block--modifier
   ..
   &__element {
     // CSS declarations for .block--modifier__element
   }
 }

}

```