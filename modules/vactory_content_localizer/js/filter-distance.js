function onFilterClick(content) {
  content.preventDefault();
  var distance = this.dataset.distance;
  var unit = this.dataset.unit;
  if (unit == 'km') {
    distance = distance * 1000;
  }
  var proximity_input = document.getElementById('edit-field-vactory-position-proximity-value');
  proximity_input.value = distance;
  document.querySelectorAll('a.js-filter-distance').forEach(function(button) {
    if (button.classList.contains('active')) {
      button.classList.remove('active');
    }
  });
  this.classList.add('active');
}

document.querySelectorAll('a.js-filter-distance').forEach(function(button) {
  var proximity_input = document.getElementById('edit-field-vactory-position-proximity-value');
  var unit = button.dataset.unit;
  var distance = proximity_input.value;
  if (unit == 'km') {
    distance = proximity_input.value / 1000;
  }
  if (button.dataset.distance == distance) {
    button.classList.add('active');
  }
  button.addEventListener('click', onFilterClick);
});
