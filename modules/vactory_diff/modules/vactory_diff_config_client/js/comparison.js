(function ($, Drupal) {
  'use strict';

  /**
   * Démarre le processus de comparaison.
   */
  window.startComparison = function () {
    const loadingElement = document.getElementById('comparison-loading');
    const resultsElement = document.getElementById('comparison-results');
    
    if (loadingElement) {
      loadingElement.style.display = 'block';
    }
    if (resultsElement) {
      resultsElement.innerHTML = '';
    }
    
    fetch('/admin/config/development/vactory-diff/ajax/compare', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    .then(response => response.json())
    .then(data => {
      if (loadingElement) {
        loadingElement.style.display = 'none';
      }
      
      if (data.success) {
        if (resultsElement) {
          resultsElement.innerHTML = data.results_html;
        }
      } else {
        if (resultsElement) {
          resultsElement.innerHTML = 
            '<div class="messages messages--error">' + data.message + '</div>';
        }
      }
    })
    .catch(error => {
      if (loadingElement) {
        loadingElement.style.display = 'none';
      }
      if (resultsElement) {
        resultsElement.innerHTML = 
          '<div class="messages messages--error">Erreur de connexion : ' + error.message + '</div>';
      }
    });
  };

  /**
   * Initialisation lors du chargement de la page.
   */
  Drupal.behaviors.vactoryDiffComparison = {
    attach: function (context, settings) {
      // Interface simple sans interactions complexes
    }
  };

})(jQuery, Drupal);
