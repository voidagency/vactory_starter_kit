/**
 * Simulateur Credit (Calcul écheance) JS.
 */

(function ($, drupalSettings) {
  var simulateur_data = {
    mode_profile: drupalSettings.vactory_simulateur.mode_profile,
    profiles: drupalSettings.vactory_simulateur.profiles,
  };

  if (simulateur_data.mode_profile === 1) {
    //Valeurs par defaut si le mode profile est séléctionner.
    //Par defaut selectionner la première option.
    var profiles_options=$(".simulateur-credit-profile").children('option').length;
    if (profiles_options > 1) {
      $('.simulateur-credit-profile').prop('selectedIndex', 1);
    }
    //Récuperer l'id du taxonomie séléctionner. {taxonomie : profiles}
    var selected_profile = $('.simulateur-credit-profile option').filter(':selected').val();
    //Récuperer l'element correspond à l'id séléctionner.
    var data = search(selected_profile, simulateur_data.profiles);
    if (typeof data !== 'undefined') {
      setMontant(data, true);
      setTaux(data, true);
      setDuree(data, true);
      setMensualites();
      setCoutTotal();
    }
    // Déclencher l'evenement Change, si la valeur du taxonomie est changé.
    $('.simulateur-credit-profile').on('change', function(e) {
      //Récuperer l'element correspond à l'id séléctionner.
      var data = search(e.target.value, simulateur_data.profiles);
      if (typeof  data !== 'undefined') {
        setMontant(data, true);
        setTaux(data, true);
        setDuree(data, true);
        setMensualites();
        setCoutTotal();
      }
    });
  }

  // Valeurs par defaut si le mode profile n'est pas séléctionner
  if (simulateur_data.mode_profile === 0) {
    // Mode sans profile.
    var _data = simulateur_data.profiles[0];
    //Default values for Montant, Montant Range.
    if (typeof _data !== 'undefined') {
      setMontant(_data, false);
      setTaux(_data, false);
      setDuree(_data, false);
      setMensualites();
      setCoutTotal();
    }

  }

  /* ----------------- Début : Evenements ----------------- */
  // Event change on Montant input.
  $('.simulateur-credit-montant').on('change keyup', function(e) {
    var _timer = setTimeout(function (){
      var montant_min = parseFloat($('.simulateur-credit-montant-range').attr('min'));
      var montant_max = parseFloat($('.simulateur-credit-montant-range').attr('max'));
      var montant = parseFloat(e.target.value);
      if ( montant <= montant_max && montant >= montant_min ) {
        $('.simulateur-credit-montant-range').val(montant);
        $('#simulateur-montant-credit').text(montant);
        //Recalcule Mensualite.
        setMensualites();
      }
      else {
        $('.simulateur-credit-montant').val(getMontantRange());
      }
      clearTimeout(_timer);
    }, 1000);
  });

  //Event change on Montant range.
  $('.simulateur-credit-montant-range').on('input', function(e) {
    var montant_range = e.target.value;
    var montant_min = parseFloat($('.simulateur-credit-montant-range').attr('min'));
    var montant_max = parseFloat($('.simulateur-credit-montant-range').attr('max'));

    if ( montant_range <= montant_max && montant_range >= montant_min ) {
      $('.simulateur-credit-montant').val(montant_range);
      $('#simulateur-montant-credit').text(montant_range);
      //Recalcule Mensualite.
      setMensualites();
    }
  });

  // Event change, keyup on Durée input.
  $('.simulateur-credit-duree').on('change keyup', function(e) {
    var _timer = setTimeout(function (){
      var duree_min = parseFloat($('.simulateur-credit-duree-range').attr('min'));
      var duree_max = parseFloat($('.simulateur-credit-duree-range').attr('max'));
      var duree = parseFloat(e.target.value);
      if ( duree <= duree_max && duree >= duree_min ) {
        $('.simulateur-credit-duree-range').val(duree);
        $('#simulateur-duree').text(duree);
        //Recalcule Mensualite.
        setMensualites();
      }
      else {
        $('.simulateur-credit-duree').val($('.simulateur-credit-duree-range').val());
      }
      clearTimeout(_timer);
    }, 1000);
  });

  //Event change on Durée range.
  $('.simulateur-credit-duree-range').on('input', function(e) {
    var duree_range = e.target.value;
    var duree_min = parseFloat($('.simulateur-credit-duree-range').attr('min'));
    var duree_max = parseFloat($('.simulateur-credit-duree-range').attr('max'));

    if ( duree_range <= duree_max && duree_range >= duree_min ) {
      $('.simulateur-credit-duree').val(duree_range);
      $('#simulateur-duree').text(duree_range);
      //Recalcule Mensualite.
      setMensualites();
    }
  });



// Event change, keyup on Taux input.
  $('.simulateur-credit-taux').on('change keyup', function(e) {
    var _timer = setTimeout(function (){
      var taux_min = parseFloat($('.simulateur-credit-taux-range').attr('min'));
      var taux_max = parseFloat($('.simulateur-credit-taux-range').attr('max'));
      var taux = parseFloat(e.target.value);
      if ( taux <= taux_max && taux >= taux_min ) {
        $('.simulateur-credit-taux-range').val(taux);
        $('#simulateur-taux').text(taux);
        //Recalcule Mensualite.
        setMensualites();
      }
      else {
        $('.simulateur-credit-taux').val($('.simulateur-credit-taux-range').val());
      }
      clearTimeout(_timer);
    }, 1000);
  });

  //Event change on Taux range.
  $('.simulateur-credit-taux-range').on('input', function(e) {
    var taux_range = e.target.value;
    var taux_min = parseFloat($('.simulateur-credit-taux-range').attr('min'));
    var taux_max = parseFloat($('.simulateur-credit-taux-range').attr('max'));

    if ( taux_range <= taux_max && taux_range >= taux_min ) {
      $('.simulateur-credit-taux').val(taux_range);
      $('#simulateur-taux').text(taux_range);
      //Recalcule Mensualite.
      setMensualites();
    }
  });


  // Event change, keyup on Taux input.
  $('.simulateur-credit-mensualite').on('change keyup', function(e) {
    var _timer = setTimeout(function (){
      var mensualite_min = parseFloat($('.simulateur-credit-mensualite-range').attr('min'));
      var mensualite_max = parseFloat($('.simulateur-credit-mensualite-range').attr('max'));
      var mensualite = parseFloat(e.target.value);
      if ( mensualite <= mensualite_max && mensualite >= mensualite_min ) {
        $('.simulateur-credit-mensualite-range').val(mensualite);
        $('#simulateur-mensualite').text(mensualite);
        //Recalcule Durée.
        setDureeAfterMensualiteChange();
        setCoutTotal();
      }
      else {
        $('.simulateur-credit-mensualite').val($('.simulateur-credit-mensualite-range').val());
      }
      clearTimeout(_timer);
    }, 1000);
  });

  //Event change on Taux range.
  $('.simulateur-credit-mensualite-range').on('input', function(e) {
    var mensualite_range = e.target.value;
    var mensualite_min = parseFloat($('.simulateur-credit-mensualite-range').attr('min'));
    var mensualite_max = parseFloat($('.simulateur-credit-mensualite-range').attr('max'));

    if ( mensualite_range <= mensualite_max && mensualite_range >= mensualite_min ) {
      $('.simulateur-credit-mensualite').val(mensualite_range);
      $('#simulateur-mensualite').text(mensualite_range);
      //Recalcule Durée.
      setDureeAfterMensualiteChange();
      setCoutTotal();
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
    $('.simulateur-credit-montant').val(data.simulateur_montant.v_simulateur_cf_montant);
    $('#simulateur-montant-credit').text(data.simulateur_montant.v_simulateur_cf_montant);
    $('.simulateur-credit-montant-range').prop({
      'min': data.simulateur_montant.v_simulateur_cf_montant_min, //min_price
      'max': data.simulateur_montant.v_simulateur_cf_montant_max //v_simulateur_cf_montant_max
    });
    $('.simulateur-credit-montant-range').val(data.simulateur_montant.v_simulateur_cf_montant);
  }

  //Function to set v_simulateur_cf_taux.
  function setTaux(data, mode) {
    //Default values for Taux, Taux Range.
    $('.simulateur-credit-taux').val(data.simulateur_taux.v_simulateur_cf_taux);
    $('#simulateur-taux').text(data.simulateur_taux.v_simulateur_cf_taux);
    if (mode === false) {
      $('.simulateur-credit-taux-range').prop({
        'min': data.simulateur_taux.v_simulateur_cf_taux_min,
        'max': data.simulateur_taux.v_simulateur_cf_taux_max
      });
      $('.simulateur-credit-taux-range').val(data.simulateur_taux.v_simulateur_cf_taux);
    }
  }

  //Function to set Time.
  function setDuree(data, mode) {
    //Default values for Durée, Durée Range.
    $('.simulateur-credit-duree').val(data.simulateur_duree.v_simulateur_cf_duree);
    $('#simulateur-duree').text(data.simulateur_duree.v_simulateur_cf_duree);
    $('.simulateur-credit-duree-range').prop({
      'min': data.simulateur_duree.v_simulateur_cf_duree_min,
      'max': data.simulateur_duree.v_simulateur_cf_duree_max
    });
    $('.simulateur-credit-duree-range').val(data.simulateur_duree.v_simulateur_cf_duree);
    setCoutTotal();
  }

  function setMensualites() {
    var Montant = getMontant();
    var Taux = getTaux();
    var mensualite = null;
    var Duree = getDuree();
    if (Taux != 0) {
      mensualite = (Montant * ((Taux/100) / 12)) / (1 - Math.pow(1 + ((Taux/100) / 12), -1 * Duree));
    }
    else {
      mensualite = Montant / Duree;
    }
    $('.simulateur-credit-mensualite').val(mensualite.toFixed(2));
    $('#simulateur-mensualites').text(mensualite.toFixed(2));
    var Duree_max = parseFloat($('.simulateur-credit-duree-range').attr('max'));
    var Duree_min = parseFloat($('.simulateur-credit-duree-range').attr('min'));
    var mensualite_max = (Montant * ((Taux/100) / 12)) / (1 - Math.pow(1 + ((Taux/100) / 12), -1 * Duree_min));
    var mensualite_min = (Montant * ((Taux/100) / 12)) / (1 - Math.pow(1 + ((Taux/100) / 12), -1 * Duree_max));

    $('.simulateur-credit-mensualite-range').prop({
      'min': mensualite_min.toFixed(2),
      'max': mensualite_max.toFixed(3)
    });

    $('.simulateur-credit-mensualite-range').val(parseInt(mensualite.toFixed(1)));
    setCoutTotal();
  }

  function setCoutTotal() {
    var cout_total = parseFloat(getDuree()) * parseFloat(getMensualite());
    $('#simulateur-cout-total-credit').text(cout_total.toFixed(3));
  }

  //getter
  function getMontant() {
    return $('.simulateur-credit-montant').val();
  }

  function getTaux() {
    if (simulateur_data.mode_profile === 1) {
      $selected_profile = $('.simulateur-credit-profile option').filter(":selected").val();
      var profile = search($selected_profile, simulateur_data.profiles);
      return profile.simulateur_taux.v_simulateur_cf_taux;
    }
    return $('.simulateur-credit-taux').val();
  }

  function getDuree() {
    return $('.simulateur-credit-duree').val();
  }

  function getMontantRange() {
    return $('.simulateur-credit-montant-range').val();
  }

  function getTauxRange() {
    return $('.simulateur-credit-taux-range').val();
  }

  function getDureeRange() {
    return $('.simulateur-credit-duree-range').val();
  }

  function getMensualite() {
    return $('.simulateur-credit-mensualite').val();
  }
  /* ----------------- Fin : Setter && Getter ----------------- */

})(jQuery, drupalSettings);
