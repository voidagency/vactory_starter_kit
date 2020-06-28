## Date Picker
```html
<!-- Datepickers -->
<div id="datepickers" class="container">
  <div class="section-title col-lg-8 col-md-10 ml-auto mr-auto">
    <h3 class="mb-4">Datepickers</h3>
    <p>Datepickers are also available and similar to slider controls they are
      <strong>very easy to create</strong>, being based on a single input element. Datepickers are customisable as well, letting
      you create complex configurations with range selections for example.</p>
  </div>

  <div class="example col-lg-8 col-md-10 ml-auto mr-auto">
    <div class="row">
      <div class="col-lg-4 col-md-12">
        <div class="form-group">
          <label for="datepicker-example-1">Date of Birth</label>
          <div class="input-group with-addon-icon-left">
            <input type="text" class="datepicker form-control" id="datepicker-example-1" placeholder="Date of Birth">
            <span class="input-group-append">
              <span class="input-group-text">
                  <i class="fa fa-calendar"></i>
              </span>
            </span>
          </div>
        </div>
      </div>
      <div class="col-lg-8 col-md-12">
        <div class="form-group">
          <label>Employment Period</label>
          <div class="input-daterange input-group datepicker" id="datepicker-example-2">
            <span class="input-group-prepend">
              <span class="input-group-text">
                <i class="fa fa-calendar"></i>
              </span>
            </span>
            <input type="text" class="input-sm form-control" name="start" placeholder="Start Date"/>
            <span class="input-group-middle"><span class="input-group-text">-</span></span>
            <input type="text" class="input-sm form-control" name="end" placeholder="End Date" />
            <span class="input-group-append">
              <span class="input-group-text">
                <i class="fa fa-calendar"></i>
              </span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
```