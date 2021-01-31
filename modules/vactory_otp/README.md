# Vactory OTP

Ce module permet d'envoyer des OTP via Mail ou SMS.
L'envoi des SMS se fait via InfoBip.

# Features
Ce module expose un service qui contient deux méthodes :
- sendOtpByMail : Cette fonction accepte 3 arguments
    - subject : l'Objet du mail.
    - to_mail : Email du déstinataire.
    - mail_body (optionnel) : Le mail qui sera envoyé, l'otp sera rajouté en 
 dessous du mail. Si ce champ est vide, le mail par defaut configuré sera 
 envoyé.
    - otp (optionnel) : si ce champ n'est pas renseigné, un OTP sera généré 
 automatiquement.

- sendOtpBySms : Cette fonction accepte 3 arguments
    - sms_phone : Le numéro de téléphone du destinataire
    - sms_body (optionnel) : Le message qui sera envoyé, l'otp sera rajouté 
à la fin du message. Si ce champ est vide, le message par defaut configuré 
sera envoyé.
    - otp (optionnel) : si ce champ n'est pas renseigné, un OTP sera généré 
automatiquement.

### Configuration
 - Page de configuration '/admin/config/development/vactory_otp_settings_form'
    - Url : Endpoint pour l'envoi des SMS (InfoBip)
    - Api Key : API key (InfoBip)
    - From : Nom du destinateur
    - Cooldown : La période que doit attendre l'utilisateur avant de pouvoir
     ré-envoyer un autre OTP.
    - Default SMS body : Message envoyé par défaut
    - Default mail body : Email envoyé par défaut
 - Page de test : '/admin/config/development/vactory_otp_test_form'
