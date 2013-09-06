<?php
/*
* Copyright 2007-2011 Charles du Jeu <contact (at) cdujeu.me>
* This file is part of AjaXplorer.
*
* AjaXplorer is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* AjaXplorer is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with AjaXplorer.  If not, see <http://www.gnu.org/licenses/>.
*
* The latest code can be found at <http://www.ajaxplorer.info/>.
*/
$mess=array(
"File System (Standard)" => "Fichiers locaux (Standard)",
"The most standard access to a filesystem located on the server." => "Driver le plus courant : accès à un répertoire local situé sur le serveur où est installé AjaXplorer",
"Path" => "Chemin",
"Real path to the root folder on the server" => "Chemin absolu sur le serveur du répertoire de base. Utilisez AJXP_USER pour remplacer automatiquement avec le login du user actuel.",
"Create" => "Création",
"Create folder if it does not exists" => "Création automatique du répertoire s'il n'existe pas, notamment utile avec AJXP_USER.",
"File Creation Mask" => "Masque de création",
"Optionnaly apply a chmod operation. Value must be numeric, like 0777, 0644, etc." => "Droits d'accès par défaut lors de la création de fichiers. Valeur numérique, de type 0777, 0644, etc.",
"Purge Days" => "Purge après...",
"Option to purge documents after a given number of days. This require a manual set up of a CRON task. Leave to 0 if you don't wan't to use this feature." => "Purger tous les documents après un certain nombre de jours. Ceci nécessite la mise en place d'un CRON, laisser à 0 pour ne pas utiliser cette feature.",
"Real Size Probing" => "Taille Réelle",
"Use system command line to get the filesize instead of php built-in function (fixes the 2Go limitation)" => "Pour contourner la limitation à 2Go, utilise un appel système pour récuperer la taille des fichiers. Peut entrer en conflit avec des limitations du php (shell_exec).",
"X-SendFile Active" => "X-Sendfile Actif",
"Delegates all download operations to the webserver using the X-SendFile header. Warning, this is an external module to install for Apache. Module is active by default in Lighttpd. Warning, you have to manually add the folders where files will be downloaded in the module configuration (XSendFilePath directive)" => "Déleguer les opérations de téléchargement au serveur web grâce au module X-SendFile. Attention, il est packagé par défaut dans Lighttpd mais doit être téléchargé et ajouté manuellement dans Apache. Il faut aussi configurer manuellement les chemins autorisés pour télécharger des fichiers, voir la directive XSendFilePath.",
"Data template" => "Données préchargées",
"Path to a directory on the filesystem whose content will be copied to the repository the first time it is loaded." => "Chemin vers un répertoire sur le filesystem dont le contenu va être copié dans le dépôt à la première connexion."
);