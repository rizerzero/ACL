ACL
===

C'est une classe PHP très simple qui utilise le système role/ressource.
Un système de scope permet de diviser les droits en module.

Utilisation
===========
```php
<?php
// Initialisation
require 'Acl.php';
$acl = new Acl();

// Création des roles (avec héritage)
$acl->addRole('papy');
$acl->addRole('papa', 'papy');
$acl->addRole('maman');
$acl->addRole('moi', array('papa', 'maman'));
$acl->addRole('enfant', 'moi');
	
// Les ressources
$acl->addResource('chezPapy');
$acl->addResource('chezParent');
$acl->addResource('chezMoi');
	
// Les droits
$acl->allow('papy', 'chezPapy');
$acl->allow('papa', 'chezParent');
$acl->allow('maman', 'chezParent');
$acl->allow('moi', 'chezMoi');

// Un petit test de l'héritage
// Ici : "moi" et "enfant" n'ont plus accès à "chezPapy"
$acl->deny('papa', 'chezPapy');

// Pour le débuggage : affichage des roles, ressources, droits
echo $acl->debug();
