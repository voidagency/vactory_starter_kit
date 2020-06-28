## Form Controls
All form controls are improved with micro-transitions and shadows that bring depth and improve the overall user experience.

### Login Forms
```html
<div class="card card-outline-secondary">
    <div class="card-header">
        <h3 class="mb-0">Login</h3>
    </div>
    <div class="card-body">
        <form class="form" role="form" autocomplete="off" id="loginForm" novalidate="" method="POST">
            <div class="form-group">
                <label for="uname1">Username</label>
                <input type="text" class="form-control" name="uname1" id="uname1" required>
                <div class="invalid-feedback">Please enter your username or email</div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" id="pwd1" required autocomplete="new-password">
                <div class="invalid-feedback">Please enter a password</div>
            </div>
            <div class="form-check small">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input"> <span>Remember me on this computer</span>
                </label>
            </div>
            <button type="submit" class="btn btn-success btn-lg float-right" id="btnLogin">Login</button>
        </form>
    </div>
</div>
```

### Sign Up Forms
```html
<div class="card card-outline-secondary">
    <div class="card-header">
        <h3 class="mb-0">Sign Up</h3>
    </div>
    <div class="card-body">
        <form class="form" role="form" autocomplete="off">
            <div class="form-group">
                <label for="inputName">Name</label>
                <input type="text" class="form-control" id="inputName" placeholder="Full name">
            </div>
            <div class="form-group">
                <label for="inputEmail3">Email</label>
                <input type="email" class="form-control" id="inputEmail3" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label for="inputPassword3">Password</label>
                <input type="password" class="form-control" id="inputPassword3" placeholder="Password" required>
            </div>
            <div class="form-group">
                <label for="inputVerify3">Verify</label>
                <input type="password" class="form-control" id="inputVerify3" placeholder="Password (again)" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-success btn-lg float-right">Register</button>
            </div>
        </form>
    </div>
</div>
```

### User Information Forms
```html
<!-- form user info -->
<div class="card card-outline-secondary">
    <div class="card-header">
        <h3 class="mb-0">User Information</h3>
    </div>
    <div class="card-body">
        <form class="form" role="form" autocomplete="off">
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label">First name</label>
                <div class="col-lg-9">
                    <input class="form-control" type="text" value="Jane">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label">Last name</label>
                <div class="col-lg-9">
                    <input class="form-control" type="text" value="Bishop">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label">Email</label>
                <div class="col-lg-9">
                    <input class="form-control" type="email" value="email@gmail.com">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label">Company</label>
                <div class="col-lg-9">
                    <input class="form-control" type="text" value="">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label">Website</label>
                <div class="col-lg-9">
                    <input class="form-control" type="url" value="">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label">Time Zone</label>
                <div class="col-lg-9">
                    <select id="user_time_zone" class="form-control" size="0">
                        <option value="Hawaii">(GMT-10:00) Hawaii</option>
                        <option value="Alaska">(GMT-09:00) Alaska</option>
                        <option value="Pacific Time (US &amp; Canada)">(GMT-08:00) Pacific Time (US &amp; Canada)</option>
                        <option value="Arizona">(GMT-07:00) Arizona</option>
                        <option value="Mountain Time (US &amp; Canada)">(GMT-07:00) Mountain Time (US &amp; Canada)</option>
                        <option value="Central Time (US &amp; Canada)" selected="selected">(GMT-06:00) Central Time (US &amp; Canada)</option>
                        <option value="Eastern Time (US &amp; Canada)">(GMT-05:00) Eastern Time (US &amp; Canada)</option>
                        <option value="Indiana (East)">(GMT-05:00) Indiana (East)</option>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label">Username</label>
                <div class="col-lg-9">
                    <input class="form-control" type="text" value="janeuser">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label">Password</label>
                <div class="col-lg-9">
                    <input class="form-control" type="password" value="11111122333">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label">Confirm</label>
                <div class="col-lg-9">
                    <input class="form-control" type="password" value="11111122333">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-lg-3 col-form-label form-control-label"></label>
                <div class="col-lg-9">
                    <input type="reset" class="btn btn-secondary" value="Cancel">
                    <input type="button" class="btn btn-primary" value="Save Changes">
                </div>
            </div>
        </form>
    </div>
</div>
<!-- /form user info -->
```
### Contact Forms
```html
<!-- form user info -->
<div class="card card-outline-secondary">
    <div class="card-header">
        <h3 class="mb-0">Contact</h3>
    </div>
    <div class="card-body">
        <form class="form" role="form" autocomplete="off">
            <fieldset>
                <label for="name2" class="mb-0">Name</label>
                <div class="row mb-1">
                    <div class="col-lg-12">
                        <input type="text" name="name2" id="name2" class="form-control" required>
                    </div>
                </div>
                <label for="email2" class="mb-0">Email</label>
                <div class="row mb-1">
                    <div class="col-lg-12">
                        <input type="text" name="email2" id="email2" class="form-control" required>
                    </div>
                </div>
                <label for="message2" class="mb-0">Message</label>
                <div class="row mb-1">
                    <div class="col-lg-12">
                        <textarea rows="6" name="message2" id="message2" class="form-control" required></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary btn-lg float-right">Send Message</button>
            </fieldset>
        </form>
    </div>
</div>
<!-- /form user info -->
```

### Simple Forms
```html
<!-- Form Controls -->
<div class="card card-outline-secondary">
  <div id="forms" class="container">
      <div class="row mb-3">
        <div class="col-md-12">
          <form>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="form1-name" class="col-form-label">Name</label>
                <input type="text" class="form-control" id="form1-name" placeholder="Name">
              </div>
              <div class="form-group col-md-6">
                <label for="form1-email" class="col-form-label">Email</label>
                <input type="email" class="form-control" id="form1-email" placeholder="Email">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-4">
                <label for="form1-password">Password</label>
                <input type="password" class="form-control" id="form1-password" placeholder="Password" required>
              </div>
              <div class="col-md-6 mb-4">
                <label for="form1-state">State</label>
                <select class="" id="form1-state">
                  <option value="" selected>Choose One</option>
                  <option value="1">California</option>
                  <option value="2">Virginia</option>
                  <option value="3">Texas</option>
                  <option value="4">Florida</option>
                </select>
              </div>

              <div class="col-md-6">
                <label for="form1-zip">Zip</label>
                <input type="text" class="form-control" id="form1-zip" placeholder="Zip" required>
              </div>
            </div>
          </form>
        </div>
      </div>
  </div>
</div>    
```
### Complex Forms
```html
<!-- form complex example -->
<div class="card card-outline-secondary">
  <div class="form-row m-4">
      <div class="col-sm-5 pb-3">
          <label for="exampleAccount">Account #</label>
          <input type="text" class="form-control" id="exampleAccount" placeholder="XXXXXXXXXXXXXXXX">
      </div>
      <div class="col-sm-3 pb-3">
          <label for="exampleCtrl">Control #</label>
          <input type="text" class="form-control" id="exampleCtrl" placeholder="0000">
      </div>
      <div class="col-sm-4 pb-3">
          <label for="exampleAmount">Amount</label>
          <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text">$</span></div>
              <input type="text" class="form-control" id="exampleAmount" placeholder="Amount">
          </div>
      </div>
      <div class="col-sm-6 pb-3">
          <label for="exampleFirst">First Name</label>
          <input type="text" class="form-control" id="exampleFirst">
      </div>
      <div class="col-sm-6 pb-3">
          <label for="exampleLast">Last Name</label>
          <input type="text" class="form-control" id="exampleLast">
      </div>
      <div class="col-sm-6 pb-3">
          <label for="exampleCity">City</label>
          <input type="text" class="form-control" id="exampleCity">
      </div>
      <div class="col-sm-3 pb-3">
          <label for="exampleSt">State</label>
          <select class="form-control" id="exampleSt">
              <option>Pick a state</option>
          </select>
      </div>
      <div class="col-sm-3 pb-3">
          <label for="exampleZip">Postal Code</label>
          <input type="text" class="form-control" id="exampleZip">
      </div>
      <div class="col-md-6 pb-3">
          <label for="exampleAccount">Color</label>
          <div class="form-group small">
              <div class="form-check form-check-inline">
                  <label class="form-check-label">
                      <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1" value="option1"> Blue
                  </label>
              </div>
              <div class="form-check form-check-inline">
                  <label class="form-check-label">
                      <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="option2"> Red
                  </label>
              </div>
              <div class="form-check form-check-inline disabled">
                  <label class="form-check-label">
                      <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio3" value="option3" disabled=""> Green
                  </label>
              </div>
              <div class="form-check form-check-inline">
                  <label class="form-check-label">
                      <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="option4"> Yellow
                  </label>
              </div>
              <div class="form-check form-check-inline">
                  <label class="form-check-label">
                      <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="option5"> Black
                  </label>
              </div>
              <div class="form-check form-check-inline">
                  <label class="form-check-label">
                      <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="option6"> Orange
                  </label>
              </div>
          </div>
      </div>
      <div class="col-md-6 pb-3">
          <label for="exampleMessage">Message</label>
          <textarea class="form-control" id="exampleMessage"></textarea>
          <small class="text-info">
            Add the packaging note here.
          </small>
      </div>
      <div class="col-12">
          <div class="form-row">
              <label class="col-md col-form-label"  for="name">Generated Id</label>
              <input type="text" class="form-control col-md-4" name="gid" id="gid" />
              <label class="col-md col-form-label"  for="name">Date Assigned</label>
              <input type="text" class="form-control col-md-4" name="da" id="da" />
          </div>
      </div>
  </div>
</div>  
```

### Forms with icon
Form controls can be stylised by utilizing icons from either supported packs. They can be placed inside default
  or seamlessly integrated input group addons.

```html
<div class="container card card-outline-secondary p-3">
    <!-- Form Controls: Using Icons - Seamless -->
    <div class="row mb-2">
      <div class="col-12">
        <h6 class="text-muted">Seamless</h6>
        <form>
          <div class="row">
            <div class="form-group col-md-6">
              <div class="input-group input-group-seamless">
                <span class="input-group-prepend">
                  <span class="input-group-text">
                    <i class="icon-user"></i>
                  </span>
                </span>
                <input type="text" class="form-control" id="form1-username" placeholder="Username">
              </div>
            </div>
            <div class="form-group col-md-6">
              <div class="input-group input-group-seamless">
                <input type="password" class="form-control" id="form2-password" placeholder="Password">
                <span class="input-group-append">
                  <span class="input-group-text">
                    <i class="icon-user"></i>
                  </span>
                </span>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
</div>
```
### Inline Forms

```html
<div class="card card-outline-secondary p-4">
  <div class="form-row">
    <div class="col-md-2">
      <label class="col-form-label">label inline</label>
    </div>
      <div class="col-md-4 mb-2">
        <input type="text" class="form-control" placeholder="First name">
      </div>

    <div class="col-md-2">
      <label class="col-form-label">label inline</label>
    </div>

      <div class="col-md-4 mb-2">
        <input type="text" class="form-control" placeholder="First name">
      </div>

  </div>

  <div class="form-row">
    <div class="col-md-2">
      <label class="col-form-label">label inline</label>
    </div>
      <div class="col-md-4 mb-2">
        <input type="text" class="form-control" placeholder="First name">
      </div>

    <div class="col-md-2">
      <label class="col-form-label">label inline</label>
    </div>

      <div class="col-md-4 mb-2">
        <input type="text" class="form-control" placeholder="First name">
      </div>

  </div>
</div>  
```


### Custom Controls
The default custom form fields are improved and extended. One of the new additions being the toggle switch control.

```html
<div class="container card card-outline-secondary">
  <div class="row m-3">
    <div class="custom-controls-example col-md-3 col-sm-3 col-xs-12 pl-0">
      <h6 class="text-muted mb-2">Checkboxes</h6>
      <fieldset>
        <div class="custom-control custom-checkbox d-block my-2">
          <input type="checkbox" class="custom-control-input" id="customCheck1">
          <label class="custom-control-label" for="customCheck1">Pizza</label>
        </div>
      </fieldset>

      <fieldset>
        <div class="custom-control custom-checkbox d-block my-2">
          <input type="checkbox" class="custom-control-input" id="customCheck2" checked>
          <label class="custom-control-label" for="customCheck2">Pasta</label>
        </div>
      </fieldset>

      <fieldset disabled>
        <div class="custom-control custom-checkbox d-block my-2">
          <input type="checkbox" class="custom-control-input" id="customCheck3">
          <label class="custom-control-label" for="customCheck3">Burgers</label>
        </div>
      </fieldset>

      <fieldset disabled>
        <div class="custom-control custom-checkbox d-block my-2">
          <input type="checkbox" class="custom-control-input" id="customCheck4" checked>
          <label class="custom-control-label" for="customCheck4">Tacos</label>
        </div>
      </fieldset>
    </div>
    <div class="custom-controls-example col-md-3 col-sm-3 col-xs-12">
      <h6 class="text-muted mb-2">Radio Buttons</h6>
      <fieldset>
        <div class="custom-control custom-radio d-block my-2">
          <input type="radio" id="customRadio1" name="customRadio" class="custom-control-input">
          <label class="custom-control-label" for="customRadio1">Cookies</label>
        </div>
      </fieldset>

      <fieldset>
        <div class="custom-control custom-radio d-block my-2">
          <input type="radio" id="customRadio2" name="customRadio" class="custom-control-input" checked>
          <label class="custom-control-label" for="customRadio2">Pancakes</label>
        </div>
      </fieldset>

      <fieldset disabled>
        <div class="custom-control custom-radio d-block my-2">
          <input type="radio" id="customRadio3" name="customRadioDisabled" class="custom-control-input">
          <label class="custom-control-label" for="customRadio3">Chocolate</label>
        </div>
      </fieldset>

      <fieldset disabled>
        <div class="custom-control custom-radio d-block my-2">
          <input type="radio" id="customRadio4" name="customRadioDisabled" class="custom-control-input" checked>
          <label class="custom-control-label" for="customRadio4">Pancakes</label>
        </div>
      </fieldset>
    </div>
    <div class="custom-controls-example col-md-4 col-sm-4 col-xs-12 pl-5">
      <h6 class="text-muted mb-1">Toggles</h6>
      <fieldset>
        <div class="custom-control custom-toggle d-block my-2">
          <input type="checkbox" id="customToggle1" name="customToggle1" class="custom-control-input">
          <label class="custom-control-label" for="customToggle1">Rockets</label>
        </div>
      </fieldset>

      <fieldset>
        <div class="custom-control custom-toggle d-block my-2">
          <input type="checkbox" id="customToggle2" name="customToggle2" class="custom-control-input" checked>
          <label class="custom-control-label" for="customToggle2">Lasers</label>
        </div>
      </fieldset>

      <fieldset disabled>
        <div class="custom-control custom-toggle d-block my-2">
          <input type="checkbox" id="customToggle3" name="customToggle3" class="custom-control-input">
          <label class="custom-control-label" for="customToggle3">HAL 9K</label>
        </div>
      </fieldset>

      <fieldset disabled>
        <div class="custom-control custom-toggle d-block my-2">
          <input type="checkbox" id="customToggle4" name="customToggle4" class="custom-control-input" checked>
          <label class="custom-control-label" for="customToggle4">Ultron</label>
        </div>
      </fieldset>
    </div>
    <div class="custom-controls-example col-md-2 col-sm-2 col-xs-12 pl-3">
      <h6 class="text-muted mb-1">Sizes</h6>

      <fieldset>
        <div class="custom-control custom-toggle custom-toggle-sm d-block my-2">
          <input type="checkbox" id="customToggle1sm" name="customToggle1sm" class="custom-control-input">
          <label class="custom-control-label" for="customToggle1sm">Rockets</label>
        </div>
      </fieldset>

      <fieldset>
        <div class="custom-control custom-toggle custom-toggle-sm d-block my-2">
          <input type="checkbox" id="customToggle2sm" name="customToggle2sm" class="custom-control-input" checked>
          <label class="custom-control-label" for="customToggle2sm">Lasers</label>
        </div>
      </fieldset>

      <fieldset disabled>
        <div class="custom-control custom-toggle custom-toggle-sm d-block my-2">
          <input type="checkbox" id="customToggle3" name="customToggle3" class="custom-control-input">
          <label class="custom-control-label" for="customToggle3">HAL</label>
        </div>
      </fieldset>

      <fieldset disabled>
        <div class="custom-control custom-toggle custom-toggle-sm d-block my-2">
          <input type="checkbox" id="customToggle4" name="customToggle4" class="custom-control-input" checked>
          <label class="custom-control-label" for="customToggle4">Ultron</label>
        </div>
      </fieldset>
    </div>
  </div>
  <div class="row m-3">
    <div class="col-md-6 pl-0 custom-dropdown-example">
      <h6 class="text-muted mb-3">Custom Dropdown</h6>
      <fieldset>
        <select class="w-100" required>
          <option value="">Select Favourite Number</option>
          <option value="1">One</option>
          <option value="2">Two</option>
          <option value="3">Three</option>
        </select>
      </fieldset>
    </div>

    <div class="col-md-6 pl-0">
      <h6 class="text-muted mb-3">Custom File Input</h6>
      <fieldset>
        <div class="custom-file w-100">
          <input type="file" class="custom-file-input" id="customFile">
          <label class="custom-file-label" for="customFile">Choose file...</label>
        </div>
      </fieldset>
    </div>
  </div>
</div>
```

### Validation
Form validation is also improved to match the new overall form feel, while following the same interaction principles for consistency.

```html
<div class="row card card-outline-secondary p-4">
  <div class="col-12">
    <form class="was-validated">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="form-2-first-name">First name</label>
          <input type="text" class="form-control is-valid" id="form-2-first-name" placeholder="First name" value="Catalin" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="form-2-last-name">Last name</label>
          <input type="text" class="form-control is-valid" id="form-2-last-name" placeholder="Last name" value="Vasile" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="form-2-city">City</label>
          <input type="text" class="form-control is-invalid" id="form-2-city" placeholder="City" required>
          <div class="invalid-feedback">
            Invalid city
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <label for="form-2-state">State</label>
          <input type="text" class="form-control is-invalid" id="form-2-state" placeholder="State" required>
          <div class="invalid-feedback">
            Invalid state
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <label for="form-2-zip">Zip</label>
          <input type="text" class="form-control is-invalid" id="form-2-zip" placeholder="Zip" required>
          <div class="invalid-feedback">
            Invalid ZIP code
          </div>
        </div>
      </div>

      <div class="row mb-2">
        <div class="col-md-6 mb-3">
          <label for="form-file-4">Photo ID Scan</label>
          <div class="custom-file w-100">
            <input type="file" class="custom-file-input" id="customFile2" required>
            <label class="custom-file-label" for="customFile2">Choose file...</label>
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="d-block" for="form-3-select">Favourite Number</label>
          <select class="w-100" id="form-3-select" required>
            <option value="">Invalid select menu</option>
            <option value="1">One</option>
            <option value="2">Two</option>
            <option value="3">Three</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="custom-control custom-checkbox mb-3">
            <input type="checkbox" class="custom-control-input" id="form-3-terms" required>
            <label class="custom-control-label" for="form-3-terms">Do you agree to our terms & conditions?</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="custom-controls-stacked d-block">
            <div class="custom-control custom-radio mb-1">
              <input type="radio" id="radioStacked1" name="radio-stacked" class="custom-control-input" required>
              <label class="custom-control-label" for="radioStacked1">Subscribe me</label>
            </div>
            <div class="custom-control custom-radio">
              <input type="radio" id="customRadio5" name="radio-stacked" class="custom-control-input" required>
              <label class="custom-control-label" for="customRadio5">Don't subscribe me</label>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
```

### JavaScript Validation
Form validation with jquery validation plugin

```html
<div class="node-webform card card-outline-secondary p-4">
   <form id="form-validation-styleguide" accept-charset="UTF-8" novalidate>
      <div class="form-group row">
         <label class="form-control-label col-form-label col-md-3" for="vactory_form--nom">Nom</label>
         <div class="col-md-9">
            <input type="text" class="form-control" name="name" id="vactory_form--nom" placeholder="nom" required>
         </div>
      </div>
      <div class="form-group row">
         <label class="form-control-label col-form-label col-md-3" for="vactory_form--prenom">Prenom</label>
         <div class="col-md-9">
            <input type="text" class="form-control" name="prenom" id="vactory_form--prenom" placeholder="prenom" required>
         </div>
      </div>
      <div class="form-group row">
         <label class="form-control-label col-form-label col-md-3" for="vactory_form--email">Email</label>
         <div class="col-md-9">
            <input type="email" class="form-control" name="email" id="vactory_form--email" placeholder="" required>
         </div>
      </div>
      <div class="form-group row">
         <label class="form-control-label col-form-label col-md-3" for="vactory_form--email_confirmation">Email-confirmation</label>
         <div class="col-md-9">
            <input type="webform_email_confirm" class="form-control" name="email-confirmation" id="vactory_form--email_confirmation" placeholder="" required>
         </div>
      </div>
      <div class="form-group row">
         <label class="form-control-label col-form-label col-md-3" for="vactory_form--website">Website</label>
         <div class="col-md-9">
            <input type="text" class="form-control" name="url" id="vactory_form--website" placeholder="" required>
         </div>
      </div>
      <div class="form-group row">
         <label class="form-control-label col-form-label col-md-3" for="vactory_form--certifica">Certifica</label>
         <div class="col-md-9">
            <div class="custom-file">
               <input type="file" class="custom-file-input" name="certifica" id="vactory_form--certifica" placeholder="" required>
               <label for="vactory_form--certifica" class="custom-file-label">chose a file...</label>
            </div>
         </div>
      </div>
      <div class="form-group row">
         <label class="form-control-label col-form-label col-md-3" for="vactory_form--range">Range</label>
         <div class="col-md-9">
            <input type="range" class="form-control" name="range" id="vactory_form--range">
         </div>
      </div>
      <div class="form-group row">
         <label class="form-control-label col-form-label col-md-3" for="vactory_form--message">Message</label>
         <div class="col-md-9">
            <textarea class="form-control" name="message" id="vactory_form--message" placeholder=""></textarea>
         </div>
      </div>
      <div class="form-group row">
         <label class="form-control-label col-form-label col-md-3" for="vactory_form--time_picker">Time picker</label>
         <div class="col-md-9">
            <input type="time" class="form-control" name="time picker" id="vactory_form--time_picker" placeholder="">
         </div>
      </div>
      <div class="form-group">
         <div class="custom-control custom-radio">
            <input type="radio" class="custom-control-input" name="toggle" id="valide" value="valide">
            <label class="custom-control-label" for="valide">valide</label>
         </div>
         <div class="custom-control custom-radio">
            <input type="radio" class="custom-control-input" name="toggle" id="invalide" value="invalide">
            <label class="custom-control-label" for="invalide">invalide</label>
         </div>
      </div>
      <div class="form-group">
         <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" name="checkbox" id="vactory_form--checkbox" required>
            <label class="custom-control-label" for="vactory_form--checkbox">Checkbox</label>
         </div>
      </div>
      <div class="actions">
         <input class="form-submit btn btn-primary" type="submit" id="edit-submit" name="op" value="Submit">
      </div>
   </form>
</div>
```

### Form label animation
to add label animation to forms you need to add the class ( animated-label ) to the wrapper of the form control and the input field should be followed by the label

```html
<div class="card card-outline-secondary p-4">
   <form id="form-label-animation" accept-charset="UTF-8" novalidate>
    <div class="form-row">

      <div class="form-group col-md-6 animated-label">
        <input type="text" class="form-control" name="name" id="vactory_form--nom2" placeholder="nom" required>
        <label class="form-control-label" for="vactory_form--nom2">Nom</label>
      </div>

      <div class="form-group col-md-6 animated-label">
      <input type="text" class="form-control" name="prenom" id="vactory_form--prenom2" placeholder="prenom" required>
      <label class="form-control-label" for="vactory_form--prenom2">Prenom</label>
      </div>

      <div class="form-group col-md-6 animated-label">
        <input type="email" class="form-control" name="email" id="vactory_form--email2" placeholder="" required>
        <label class="form-control-label" for="vactory_form--email2">Email</label>
      </div>

      <div class="form-group col-md-6 animated-label">
        <input type="webform_email_confirm" class="form-control" name="email-confirmation" id="vactory_form--email_confirmation2" placeholder="" required>
        <label class="form-control-label" for="vactory_form--email_confirmation2">Email-confirmation</label>
      </div>

      <div class="form-group col-md-12 animated-label">
        <input type="text" class="form-control" name="url" id="vactory_form--website2" placeholder="" required>
        <label class="form-control-label" for="vactory_form--website2">Website</label>
      </div>

      <div class="form-group col-md-12 animated-label">
        <textarea class="form-control" name="message" id="vactory_form--message2" placeholder="" required></textarea>
        <label class="form-control-label" for="vactory_form--message2">Message</label>
      </div>

      <div class="form-group col-md-6">
        <div class="custom-control custom-radio">
          <input type="radio" class="custom-control-input" name="toggle" id="valide2" value="valide">
          <label class="custom-control-label" for="valide2">valide</label>
        </div>
        <div class="custom-control custom-radio">
          <input type="radio" class="custom-control-input" name="toggle" id="invalide2" value="invalide">
          <label class="custom-control-label" for="invalide2">invalide</label>
        </div>
      </div>

      <div class="form-group col-md-6">
        <div class="custom-control custom-checkbox">
          <input type="checkbox" class="custom-control-input" name="checkbox" id="vactory_form--checkbox2" required>
          <label class="custom-control-label" for="vactory_form--checkbox2">Checkbox</label>
        </div>
      </div>

    </div>
      <div class="actions">
         <input class="form-submit btn btn-primary" type="submit" id="edit-submit" name="op" value="Submit">
      </div>
   </form>
</div>
```