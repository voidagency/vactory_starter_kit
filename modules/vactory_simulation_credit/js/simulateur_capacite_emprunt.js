/**
 * Simulateur Credit (Capacité emprunt) JS.
 */

(function ($, drupalSettings) {
  var simulateur_data = {
    mode_profile: drupalSettings.vactory_simulateur.mode_profile,
    profiles: drupalSettings.vactory_simulateur.profiles,
  };
  if (simulateur_data.mode_profile === 1) {
    //Valeurs par defaut si le mode profile est séléctionner.
    //Par defaut selectionner la première option.
    var profiles_options=$(".simulateur-capacite-credit-profile").children('option').length;
    if (profiles_options > 1) {
      $('.simulateur-capacite-credit-profile').prop('selectedIndex', 1);
    }
    //Récuperer l'id du taxonomie séléctionner. {taxonomie : profiles}
    var selected_profile = $('.simulateur-capacite-credit-profile option').filter(':selected').val();
    //Récuperer l'element correspond à l'id séléctionner.
    var data = search(selected_profile, simulateur_data.profiles);
    if (typeof data !== 'undefined') {
      setMontant(data, 'default');
      setTaux(data, true);
      setDuree(data);
      setMensualites(data);
      setCoutTotal();
    }
    // Déclencher l'evenement Change, si la valeur du taxonomie est changé.
    $('.simulateur-capacite-credit-profile').on('change', function(e) {
      //Récuperer l'element correspond à l'id séléctionner.
      var data = search(e.target.value, simulateur_data.profiles);
      if (typeof  data !== 'undefined') {
        setMontant(data);
        setTaux(data, true);
        setDuree(data);
        setMensualites(data);
        setCoutTotal();
      }
    });
  }

  // Valeurs par defaut si le mode profile n'est pas séléctionner
  if (simulateur_data.mode_profile === 0) {
    // Mode sans profile.
    var _data = simulateur_data.profiles[0];
    console.log(_data);
    //Default values for Montant, Montant Range.
    if (typeof _data !== 'undefined') {
      setMontant(_data, 'default');
      setTaux(_data, false);
      setMensualites(_data);
      setDuree(_data);
      setCoutTotal();
    }

  }

  /* ----------------- Début : Evenements ----------------- */
  // Event change, keyup on Durée input.
  $('.simulateur-capacite-credit-duree').on('change keyup', function(e) {
    var _timer = setTimeout(function (){
      var duree_min = parseFloat($('.simulateur-capacite-credit-duree-range').attr('min'));
      var duree_max = parseFloat($('.simulateur-capacite-credit-duree-range').attr('max'));
      var duree = parseFloat(e.target.value);
      if ( duree <= duree_max && duree >= duree_min ) {
        $('.simulateur-capacite-credit-duree-range').val(duree);
        $('#simulateur-capacite-duree').text(duree);
        //Recalcule Le montant.
        setMontant();
      }
      else {
        $('.simulateur-capacite-credit-duree').val($('.simulateur-capacite-credit-duree-range').val());
      }
      clearTimeout(_timer);
    }, 1000);
  });

  //Event change on Durée range.
  $('.simulateur-capacite-credit-duree-range').on('input', function(e) {
    var duree_range = e.target.value;
    var duree_min = parseFloat($('.simulateur-capacite-credit-duree-range').attr('min'));
    var duree_max = parseFloat($('.simulateur-capacite-credit-duree-range').attr('max'));

    if ( duree_range <= duree_max && duree_range >= duree_min ) {
      $('.simulateur-capacite-credit-duree').val(duree_range);
      $('#simulateur-capacite-duree').text(duree_range);
      //Recalcule le montant.
      setMontant();
    }
  });

  // Event change, keyup on Taux input.
  $('.simulateur-capacite-credit-taux').on('change keyup', function(e) {
    var _timer = setTimeout(function (){
      var taux_min = parseFloat($('.simulateur-capacite-credit-taux-range').attr('min'));
      var taux_max = parseFloat($('.simulateur-capacite-credit-taux-range').attr('max'));
      var taux = parseFloat(e.target.value);
      if ( taux <= taux_max && taux >= taux_min ) {
        $('.simulateur-capacite-credit-taux-range').val(taux);
        $('#simulateur-capacite-taux').text(taux);
        //Recalcule Montant.
        setMontant();
      }
      else {
        $('.simulateur-capacite-credit-taux').val($('.simulateur-capacite-credit-taux-range').val());
      }
      clearTimeout(_timer);
    }, 1000);
  });

  //Event change on Taux range.
  $('.simulateur-capacite-credit-taux-range').on('input', function(e) {
    var taux_range = e.target.value;
    var taux_min = parseFloat($('.simulateur-capacite-credit-taux-range').attr('min'));
    var taux_max = parseFloat($('.simulateur-capacite-credit-taux-range').attr('max'));

    if ( taux_range <= taux_max && taux_range >= taux_min ) {
      $('.simulateur-capacite-credit-taux').val(taux_range);
      $('#simulateur-capacite-taux').text(taux_range);
      //Recalcule Montant.
      setMontant();
    }
  });

  // Event change, keyup on Taux input.
  $('.simulateur-capacite-credit-mensualite').on('change keyup', function(e) {
    var _timer = setTimeout(function (){
      var mensualite_min = parseFloat($('.simulateur-capacite-credit-mensualite-range').attr('min'));
      var mensualite_max = parseFloat($('.simulateur-capacite-credit-mensualite-range').attr('max'));
      var mensualite = parseFloat(e.target.value);
      if ( mensualite <= mensualite_max && mensualite >= mensualite_min ) {
        $('.simulateur-capacite-credit-mensualite-range').val(mensualite);
        $('#simulateur-capacite-mensualites').text(mensualite);
        //Recalcule Montant.
        setMontant();
      }
      else {
        $('.simulateur-capacite-credit-mensualite').val($('.simulateur-capacite-credit-mensualite-range').val());
      }
      clearTimeout(_timer);
    }, 1000);
  });

  //Event change on Taux range.
  $('.simulateur-capacite-credit-mensualite-range').on('input', function(e) {
    var mensualite_range = e.target.value;
    var mensualite_min = parseFloat($('.simulateur-capacite-credit-mensualite-range').attr('min'));
    var mensualite_max = parseFloat($('.simulateur-capacite-credit-mensualite-range').attr('max'));

    if ( mensualite_range <= mensualite_max && mensualite_range >= mensualite_min ) {
      $('.simulateur-capacite-credit-mensualite').val(mensualite_range);
      $('#simulateur-capacite-mensualites').text(mensualite_range);
      //Recalcule Montant.
     setMontant();
    }
  });

  /* ----------------- Fin : Evenements ----------------- */


  //Utile function to find objet in array.
  function search(value, myArray){

    for (var i=1; i < myArray.length; i++) {
      if (myArray[i].v_simulateur_cf_profile === value) {
        return myArray[i];
      }
    }
  }

  /* ----------------- Début : Setter && Getter ----------------- */

  function setDureeAfterMensualiteChange() {
    var montant = getMontant();
    var taux = getTaux();
    var mensualite = getMensualite();
    var resultat_duree = parseInt((-1 * Math.log(1 - (montant * (taux/100)) / (12 * mensualite))) / (Math.log(1 + (taux/100)/12)));
    $('.simulateur-credit-duree').val(resultat_duree);
    $('.simulateur-credit-duree-range').val(resultat_duree);
    $('#simulateur-duree').text(resultat_duree);
  }

  function setMontant(data, mode) {
    var mensualite = getMensualite();
    var taux = getTaux();
    var duree = getDuree();
    if (mode == 'default') {
      mensualite = data.capacite_emprunt.v_simulateur_cf_mensualite
      taux = data.simulateur_taux.v_simulateur_cf_taux
      duree = data.simulateur_duree.v_simulateur_cf_duree
    }
    var capacite_emprunt = 12 * mensualite * ((1 - Math.pow(1 + ((taux / 100) / 12), -1 * duree)) / (taux / 100));
    $('#simulateur-capacite-montant-credit').text(capacite_emprunt.toFixed(2));
    setCoutTotal();
  }

  //Function to set v_simulateur_cf_taux.
  function setTaux(data, mode) {
    //Default values for Taux, Taux Range.
    $('.simulateur-capacite-credit-taux').val(data.simulateur_taux.v_simulateur_cf_taux);
    $('#simulateur-capacite-taux').text(data.simulateur_taux.v_simulateur_cf_taux);
    if (mode === false) {
      $('.simulateur-capacite-credit-taux-range').prop({
        'min': data.simulateur_taux.v_simulateur_cf_taux_min,
        'max': data.simulateur_taux.v_simulateur_cf_taux_max
      });
      $('.simulateur-capacite-credit-taux-range').val(data.simulateur_taux.v_simulateur_cf_taux);
    }
  }

  //Function to set Time.
  function setDuree(data, mode) {
    //Default values for Durée, Durée Range.
    $('.simulateur-capacite-credit-duree').val(data.simulateur_duree.v_simulateur_cf_duree);
    $('#simulateur-capacite-duree').text(data.simulateur_duree.v_simulateur_cf_duree);
    $('.simulateur-capacite-credit-duree-range').prop({
      'min': data.simulateur_duree.v_simulateur_cf_duree_min,
      'max': data.simulateur_duree.v_simulateur_cf_duree_max
    });
    $('.simulateur-capacite-credit-duree-range').val(data.simulateur_duree.v_simulateur_cf_duree);
    //setCoutTotal();
  }

  function setMensualites(data) {
    //Default values for Durée, Durée Range.
    $('.simulateur-capacite-credit-mensualite').val(data.capacite_emprunt.v_simulateur_cf_mensualite);
    $('#simulateur-capacite-mensualites').text(data.capacite_emprunt.v_simulateur_cf_mensualite);
    $('.simulateur-capacite-credit-mensualite-range').prop({
      'min': data.capacite_emprunt.v_simulateur_cf_mensualite_min,
      'max': data.capacite_emprunt.v_simulateur_cf_mensualite_max
    });
    $('.simulateur-capacite-credit-mensualite-range').val(data.capacite_emprunt.v_simulateur_cf_mensualite);
  }

  function setCoutTotal() {
    var cout_total = parseFloat(getDuree()) * parseFloat(getMensualite());
    $('#simulateur-capacite-cout-total-credit').text(cout_total.toFixed(3));
  }

  //getter
  function getMontant() {
    return $('.simulateur-capacite-credit-montant').val();
  }

  function getTaux() {
    if (simulateur_data.mode_profile === 1) {
      $selected_profile = $('.simulateur-capacite-credit-profile option').filter(":selected").val();
      var profile = search($selected_profile, simulateur_data.profiles);
      return profile.simulateur_taux.v_simulateur_cf_taux;
    }
    return $('.simulateur-capacite-credit-taux').val();
  }

  function getDuree() {
    return $('.simulateur-capacite-credit-duree').val();
  }

  function getMontantRange() {
    return $('.simulateur-capacite-credit-montant-range').val();
  }

  function getTauxRange() {
    return $('.simulateur-capacite-credit-taux-range').val();
  }

  function getDureeRange() {
    return $('.simulateur-capacite-credit-duree-range').val();
  }

  function getMensualite() {
    return $('.simulateur-capacite-credit-mensualite').val();
  }
  /* ----------------- Fin : Setter && Getter ----------------- */

})(jQuery, drupalSettings);
