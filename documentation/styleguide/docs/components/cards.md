Une carte est un conteneur de contenu flexible et extensible. Elle inclut des options pour les en-têtes et les pieds de page, une grande variété de contenu, des couleurs d'arrière-plan contextuelles et des options d'affichage puissantes.

## Card Blocks

The building block of a card is the `.card-body`. Use it whenever you need a padded section within a card.

```html
<div class="card">
    <div class="card-body">
        This is some text within a card block.
    </div>
</div>
```
## Default Cards
Card titles are used by adding `.card-title`. In the same way, links are added and placed next to each other by adding `.btn-{color}` to an <a> tag.

Subtitles are used by adding a `.card-subtitle`. If the `.card-title` and the `.card-subtitle` items are placed in a `.card-body` item, the card title and subtitle are aligned nicely.

```html
<div class="card-deck">
  <div class="card copyable">
      <div class="card-body">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Left-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card copyable">
      <div class="card-body text-center">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Center-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card copyable">
      <div class="card-body text-right">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Right-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>
</div>
```

## Cards with icons

```html
<div class="card-deck">
  <div class="card copyable">
      <div class="card-body">
        <i class='icon icon-drupal font-20 mb-2'></i>
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Left-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card copyable">
      <div class="card-body text-center">
        <i class='icon icon-drupal font-20 mb-2'></i>
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Center-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card copyable">
      <div class="card-body text-right">
        <i class='icon icon-drupal font-20 mb-2'></i>
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Right-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>
</div>
```

## Cards with Header and Footer

Add an optional header and/or footer within a card.
  - Card headers can be styled by adding `.card-header` & footers can be styled by adding `.card-footer` 

```html
<div class="card-deck">
  <div class="card copyable">
      <div class="card-header">
        Text Aligned-left
      </div>
      <div class="card-body">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Left-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
      <div class="card-footer text-muted">
          card-footer
      </div>
  </div>

  <div class="card copyable">
      <div class="card-header">
        Text Aligned-center
      </div>
      <div class="card-body text-center">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Center-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
      <div class="card-footer text-muted">
          card-footer
      </div>
  </div>

  <div class="card copyable">
      <div class="card-header">
        Text Aligned-right
      </div>
      <div class="card-body text-right">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Right-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
      <div class="card-footer text-muted">
          card-footer
      </div>
  </div>
</div>
```
## Cards with image top

`.card-img-top` places an image to the top of the card. With `.card-text`, text can be added to the card. Text within `.card-text` can also be styled with the standard HTML tags.

```html
<div class="card-deck">
  <article class="card copyable">
    <img class="card-img-top no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
    <div class="card-body">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
        <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
  </article>

  <article class="card copyable">
    <img class="card-img-top no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
    <div class="card-body text-center">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
        <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
  </article>

  <article class="card copyable">
    <img class="card-img-top no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
    <div class="card-body text-right">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
        <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
  </article>
</div>  
```

## Cards with image bottom

`.card-img-bottom` places an image to the top of the card. With `.card-text`, text can be added to the card. Text within `.card-text` can also be styled with the standard HTML tags.

```html
<div class="card-deck">
  <article class="card copyable">
    <div class="card-body">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
        <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
    <img class="card-img-bottom no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
  </article>

  <article class="card copyable">
    <div class="card-body text-center">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
        <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
    <img class="card-img-bottom no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
  </article>

  <article class="card copyable">
    <div class="card-body text-right">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
        <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
    <img class="card-img-bottom no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
  </article>
</div>  
```

## Cards with image overlay

Turn an image into a card background and overlay your card’s text. Depending on the image, you may or may not need additional styles or utilities.

```html
<div class="card-deck">
  <article class="card no-border copyable">
    <img class="card-img no-effect" src="https://picsum.photos/id/118/286/180" alt="card-img-overlay">
    <div class="card-img-overlay">
      <h4 class="card-title text-white">Card title</h4>
      <p class="card-text text-white">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
    </div>
  </article>

  <article class="card no-border copyable">
    <img class="card-img no-effect" src="https://picsum.photos/id/118/286/180" alt="card-img-overlay">
    <div class="card-img-overlay">
      <h4 class="card-title text-white">Card title</h4>
      <p class="card-text text-white">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
    </div>
  </article>

  <article class="card no-border copyable">
    <img class="card-img no-effect" src="https://picsum.photos/id/118/286/180" alt="card-img-overlay">
    <div class="card-img-overlay">
      <h4 class="card-title text-white">Card title</h4>
      <p class="card-text text-white">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
    </div>
  </article>
</div>  
```

## Card Inline

`.card--inline` Card inline is a variation that displays the card in an inline format -
 available in desktop. Will render as a normal card on mobile.

```html
<article class="card card--inline mb-4 copyable">
  <div class="card-col position-relative card-image--inline">
    <img class="card-img no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
  </div>
  <div class="card-col position-relative card-body--inline">
    <div class="card-body">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
      <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
  </div>
</article>

<article class="card card--inline mb-4 copyable">
  <div class="card-col position-relative card-body--inline">
    <div class="card-body">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
      <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
  </div>
  <div class="card-col position-relative card-image--inline">
    <img class="card-img no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
  </div>
</article>

<article class="card card--inline mb-4 copyable">
  <div class="card-col position-relative card-image--inline">
    <img class="card-img no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
  </div>
  <div class="card-col position-relative card-body--inline">
    <div class="card-body text-center">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
      <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
  </div>
</article>


<article class="card card--inline copyable">
  <div class="card-col position-relative card-body--inline">
    <div class="card-body text-right">
      <h4 class="card-title">Card title</h4>
      <h6 class="card-subtitle mb-2">
          <span class="badge badge-primary">Badge</span>
          <span class="badge badge-danger">Badge</span>
      </h6>

      <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content some quick example text to build on the card title and make up the bulk of the card's content.</p>
      <div class="card-read-more">
      <a href="#" class="btn btn-primary">Go somewhere</a>
      </div>
    </div>
  </div>
  <div class="card-col position-relative card-image--inline">
    <img class="card-img no-effect" src="https://picsum.photos/286/180" alt="Card image cap">
  </div>
</article>
```

## Card Colors

Use text and background utilities to change the appearance of a card `.bg-{color}` .

```html
<div class="card-deck mb-4">
  <div class="card bg-primary copyable">
      <div class="card-body">
        <h4 class="card-title text-white">Card title</h4>
        <p class="card-text text-white">Left-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-secondary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card bg-secondary copyable">
      <div class="card-body">
        <h4 class="card-title text-white">Card title</h4>
        <p class="card-text text-white">Center-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card bg-success copyable">
      <div class="card-body">
        <h4 class="card-title text-white">Card title</h4>
        <p class="card-text text-white">Right-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>
</div>

<div class="card-deck">
  <div class="card bg-danger copyable">
      <div class="card-body">
        <h4 class="card-title text-white">Card title</h4>
        <p class="card-text text-white">Left-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card bg-warning copyable">
      <div class="card-body">
        <h4 class="card-title text-white">Card title</h4>
        <p class="card-text text-white">Center-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card bg-info copyable">
      <div class="card-body">
        <h4 class="card-title text-white">Card title</h4>
        <p class="card-text text-white">Right-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>
</div>
```

## Card Outline Colors

Use border utilities to change just the `border-color` of a card. Note that you can put `.text-{color}` classes on the parent `.card` or a subset of the card’s contents as shown below & add `.border` on the parent.

```html
<div class="card-deck mb-4">
  <div class="card border border-primary copyable">
      <div class="card-body">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Left-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card border border-secondary copyable">
      <div class="card-body">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Center-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card border border-success copyable">
      <div class="card-body">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Right-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>
</div>

<div class="card-deck">
  <div class="card border border-danger copyable">
      <div class="card-body">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Left-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card border border-warning copyable">
      <div class="card-body">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Center-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>

  <div class="card border border-info copyable">
      <div class="card-body">
        <h4 class="card-title">Card title</h4>
        <p class="card-text">Right-aligned content. Example text to build on the card title and make up the bulk of the card's content.</p>
        <div class="card-read-more">
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
  </div>
</div>
```