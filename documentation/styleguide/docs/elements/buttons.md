Factory includes several predefined button styles, each serving its own semantic purpose, with a few extras thrown in for more control.

## Button

`.btn` - Buttons are the essence of life and the raison d'etre of UI design. Live it, love it.

```html
<button class="btn">Button</button>
```

## Button group

`.btn-group` - Wrap a series of buttons in a group.

```html
<div class="btn-group" role="group" aria-label="Basic example">
    <button class="btn">Left</button>
    <button class="btn">Middle</button>
    <button class="btn">Right</button>
</div>
```

## Button toolbar

`.btn-toolbar` - Wrap a series of button groups in a toolbar for more complex components.

```html
<div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
    <div class="btn-group mr-2" role="group" aria-label="First group">
        <button class="btn">Cut</button>
        <button class="btn">Copy</button>
        <button class="btn">Paste</button>
    </div>
    <div class="btn-group" role="group" aria-label="Second group">
        <button class="btn">Undo</button>
        <button class="btn">Redo</button>
    </div>
</div>
```

## Contextual alternatives

Like other components, easily make a button more meaningful to a particular context by adding any of the contextual state classes. `.btn-default` `.btn-primary` `.btn-secondary` `.btn-warning` `.btn-success` `.btn-info`

```html
<button class="btn">Default</button>
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-warning">Warning</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-info">Info</button>
```

## Outline buttons

In need of a button, but not the hefty background colors they bring? Replace the default modifier classes with the `.btn-outline-*` ones to remove all background images and colors on any button.

```html
dark: true
---
<button type="button" class="btn btn-outline-primary">Primary</button>
<button type="button" class="btn btn-outline-secondary">Secondary</button>
<button type="button" class="btn btn-outline-success">Success</button>
<button type="button" class="btn btn-outline-danger">Danger</button>
<button type="button" class="btn btn-outline-warning">Warning</button>
<button type="button" class="btn btn-outline-info">Info</button>
<button type="button" class="btn btn-outline-light">Light</button>
<button type="button" class="btn btn-outline-dark">Dark</button>
```

## Buttons & Icons

Buttons can have icons too.

```html
<button class="btn btn-default prefix-icon-burger-menu js-has-icon"><i class="icon-burger-menu"></i>Default</button>
<button class="btn btn-primary suffix-icon-search js-has-icon">Primary<i class="icon-search"></i></button>
```

## Sizing

Fancy larger or smaller buttons? Add .btn-lg or .btn-sm for additional sizes.

```html
<button type="button" class="btn btn-primary btn-lg">Large button</button>
<button type="button" class="btn btn-secondary btn-lg">Large button</button>
```

```html
<button type="button" class="btn btn-primary btn-sm">Small button</button>
<button type="button" class="btn btn-secondary btn-sm">Small button</button>
```

Create block level buttons—those that span the full width of a parent—by adding `.btn-block`.

```html
<button type="button" class="btn btn-primary btn-lg btn-block">Block level button</button>
<button type="button" class="btn btn-secondary btn-lg btn-block">Block level button</button>
```

## Active state

Buttons will appear pressed (with a darker background, darker border, and inset shadow) when active. There’s no need to add a class to `<button>`s as they use a pseudo-class. However, you can still force the same active appearance with `.active` (and include the `aria-pressed="true"` attribute) should you need to replicate the state programmatically.

```html
<a href="#" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Primary link</a>
<a href="#" class="btn btn-secondary btn-lg active" role="button" aria-pressed="true">Link</a>
```

## Disabled state

Make buttons look inactive by adding the `disabled` boolean attribute to any `<button>` element.

Disabled buttons using the `<a>` element behave a bit different:

 - `<a>`s don’t support the `disabled` attribute, so you must add the
   `.disabled` class to make it visually appear disabled.
 - Some future-friendly styles are included to disable all
   `pointer-events` on anchor buttons. In browsers which support that
   property, you won’t see the disabled cursor at all.
 - Disabled buttons should include the `aria-disabled="true"` attribute to
   indicate the state of the element to assistive technologies.

```html
<a href="#" class="btn btn-primary btn-lg disabled" role="button" aria-disabled="true">Primary link</a>
<a href="#" class="btn btn-secondary btn-lg disabled" role="button" aria-disabled="true">Link</a>
```

```hint
The `.disabled` class uses `pointer-events: none` to try to disable the link functionality of `<a>`s, but that CSS property is not yet standardized. In addition, even in browsers that do support `pointer-events: none`, keyboard navigation remains unaffected, meaning that sighted keyboard users and users of assistive technologies will still be able to activate these links. So to be safe, add a `tabindex="-1"` attribute on these links (to prevent them from receiving keyboard focus) and use custom JavaScript to disable their functionality.
```