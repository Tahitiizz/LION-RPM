Module Alarm
-------------------------------------------------------------------------------------------------------------------------------------------------

alarm_v6.0.0
- passage de mysql à oracle


alarm_v1.0.4
- correction du bug 7754 : mauvais titre de fenêtre de création d'une nouvelle alarme
- correction du bug 7751 : mauvaise définition du protocole employé (erreur avec "https")

alarm_v1.0.3
- renommage de la table 'alk_service' en 'alk_services' dans "class/AlarmModel.class.php" (demande SLM)

alarm_v1.0.2
- suppression des fichiers de configuration "php/app_conf.php" et "php/app_conf_ta.php" (non utilisé par SLM)
- suppression du fichier d'exemple à la racine (non utilisé par SLM)
- modification du chemin vers le fichier de configuration dans "./index.php" pour se référer à celui de SLM

alarm_v1.0.1
- masquage du bouton de suppression du trigger
- suppression du message "(Triggers are linked using an \'AND\' condition)"

alarm_v1.0.0
version de base