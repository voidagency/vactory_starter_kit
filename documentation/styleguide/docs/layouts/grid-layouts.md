## Grid

```html|span-6,no-source,plain,light
    <div class="row text-center mb-3">
        <div class="col-6">
            <div class="bg-white p-2">Column 1</div>
        </div>
        <div class="col-6">
            <div class="bg-white p-2">Column 2</div>
        </div>
    </div>

    <div class="row text-center mb-3">
        <div class="col-4">
            <div class="bg-white p-2">Column 1</div>
        </div>
        <div class="col-4">
            <div class="bg-white p-2">Column 2</div>
        </div>
        <div class="col-4">
            <div class="bg-white p-2">Column 3</div>
        </div>
    </div>
    
    <div class="row text-center mb-3">
        <div class="col-3">
            <div class="bg-white p-2">Col 1</div>
        </div>
        <div class="col-3">
            <div class="bg-white p-2">Col 2</div>
        </div>
        <div class="col-3">
            <div class="bg-white p-2">Col 3</div>
        </div>
        <div class="col-3">
            <div class="bg-white p-2">Col 4</div>
        </div>
    </div>

    <div class="row text-center mb-3">
        <div class="col-2">
            <div class="bg-white p-2">Col 1</div>
        </div>
        <div class="col-2">
            <div class="bg-white p-2">Col 2</div>
        </div>
        <div class="col-2">
            <div class="bg-white p-2">Col 3</div>
        </div>
        <div class="col-2">
            <div class="bg-white p-2">Col 4</div>
        </div>
        <div class="col-2">
            <div class="bg-white p-2">Col 5</div>
        </div>
        <div class="col-2">
            <div class="bg-white p-2">Col 6</div>
        </div>
    </div>

    <div class="row text-center">
        <div class="col-1">
            <div class="bg-white p-2">C 1</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 2</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 3</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 4</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 5</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 6</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 7</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 8</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 9</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 10</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 11</div>
        </div>
        <div class="col-1">
            <div class="bg-white p-2">C 12</div>
        </div>
    </div>
```
## Columns

```html|span-6,no-source,plain,light
    <div class="row text-center mb-3">
        <div class="col-xl-6">
            <div class="bg-white p-2">col-xl above 1200px</div>
        </div>
        <div class="col-xl-6">
            <div class="bg-white p-2">col-xl above 1200px</div>
        </div>
    </div>

    <div class="row text-center mb-3">
        <div class="col-lg-6">
            <div class="bg-white p-2">col-lg 992px - 1199px</div>
        </div>
        <div class="col-lg-6">
            <div class="bg-white p-2">col-lg 992px - 1199px</div>
        </div>
    </div>

    <div class="row text-center mb-3">
        <div class="col-md-6">
            <div class="bg-white p-2">col-md 768px - 991px</div>
        </div>
        <div class="col-md-6">
            <div class="bg-white p-2">col-md 768px - 991px</div>
        </div>
    </div>

    <div class="row text-center mb-3">
        <div class="col-sm-6">
            <div class="bg-white p-2">col-sm 576px - 767px</div>
        </div>
        <div class="col-sm-6">
            <div class="bg-white p-2">col-sm 576px - 767px</div>
        </div>
    </div>

    <div class="row text-center mb-3">
        <div class="col-6">
            <div class="bg-white p-2">col-xs below 576px</div>
        </div>
        <div class="col-6">
            <div class="bg-white p-2">col-xs below 576px</div>
        </div>
    </div>
```

## Full-Width Layout / Contained Layout

Full-Width Layout Add class `.container-fluid` Or `.container` For Contained Layout

```html|span-6,no-source,plain,light
<div class="container-fluid">
    <div class="row text-center">
        <div class="col-12">
            <div class="bg-primary p-3 mb-3 text-white">
                Full-Width Row
            </div>
        </div>

        <div class="col-md-12 col-lg-8">
            <div class="bg-success p-5 text-white">
                Content
            </div>
        </div>

        <div class="col-md-12 col-lg-4">
            <div class="bg-info p-5 text-white">
                Sidebar
            </div>
        </div>
    </div>
</div>
```
