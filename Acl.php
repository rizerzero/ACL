<?php

/**
 * Gestion des ACL
 * @author Matthieu YOURKEVITCH <matthieuy at gmail dot com>
 */

class Acl {
    /**
     * Liste des roles
     * @var array 
     */
    private $roles = array();
    
    /**
     * Liste des resources
     * @var array
     */
    private $resources = array();
    
    /**
     * Liste des droits ACL
     * @var array
     */
    private $acl = array();
    
    /**
     * Ajouter un role
     * @param string $role Nom du role
     * @param string|array $parents Nom du role parent, vide pour aucun, un array pour plusieurs parents
     * @return Acl Les ACL
     */
    public function addRole($roles, $parent =null) {
        if($parent) {
          if(is_array($roles)) {
            foreach ($roles as $role) {
              if($this->isRoleExist($parent)&&($role!=$parent)) {
                  $this->roles[$role][] = $parent;
              } else {
              trigger_error(" le parenet $parent n' exite pas , ou il est identique au role et ne peut donc pas hériter de lui même  ", E_USER_ERROR);
              }
             }
          }elseif (is_string($roles)) {
            if($this->isRoleExist($parent)&&($roles!=$parent)) {
                $this->roles[$roles][] = $parent;
            } else {
            trigger_error(" soit le parenet $parent n' exite pas ,ou il est identique au role et ne peut donc pas hériter de lui même  ", E_USER_ERROR);
            }
            }
        }else{
        $this->roles[$roles] = array();
        }
        return $this; 
    }
    
    /**
     * Ajouter une/des ressource(s)
     * @param string|array $resources Nom de la ressource ou un array avec les ressources
     * @param string $scope Le Scope
     * @return Acl Les ACL
     */
    public function addResource($resources, $scope = 'global') {
	    if (is_string($resources)) {
        $this->resources[$scope][$resources] = '';
	    } elseif (is_array($resources)) {
	      foreach ($resources as $resource) {
		      $this->resources[$scope][$resource] = '';
	      }
	    } else {
	      trigger_error("Le paramètre doit être un string ou un array", E_USER_ERROR);
	    }
	
	    return $this;
    }
    
    /**
     * Autorisé un role sur une ou des ressources
     * @param string $role Le role
     * @param string|array $resources La/Les resource(s)
     * @param string $scope Le scope
     * @return Acl Les ACL
     */
    public function allow($role, $resources, $scope = 'global') {
	    if (is_string($resources)) {
	      $this->setAcl($role, $resources, $scope, true);
	    } elseif (is_array($resources)) {
	      foreach($resources as $resource) {
		      $this->setAcl($role, $resource, $scope, true);
	      }
	    } else {
	      trigger_error("Le paramètre resource doit être un string ou un array", E_USER_ERROR);
	    }
	
	    return $this;
    }
    
    /**
     * Refusé un role sur une ou des ressources
     * @param string $role Le role
     * @param string|array $resources Les resources
     * @param string $scope Le scope
     * @return Acl Les ACL
     */
    public function deny($role, $resources, $scope = 'global') {
	    if (is_string($resources)) {
	      $this->setAcl($role, $resources, $scope, false);
	    } elseif (is_array($resources)) {
	      foreach($resources as $resource) {
		      $this->setAcl($role, $resource, $scope, false);
	      }
	    } else {
	      trigger_error("Le paramètre resource doit être un string ou un array", E_USER_ERROR);
	    }
	
	    return $this;
    }
    
    /**
     * Vérifier si autorisé ou non
     * @param string $role Le role
     * @param string $resource La resource
     * @param string $scope Le scope
     * @return boolean Autorisé ou non
     */
    public function isAllowed($role, $resource, $scope = 'global') {
	    // On vérifie l'existance du role et de la resource
	    if ($this->isRoleExist($role) && $this->isResourceExist($resource, $scope)) {
	      // On vérifie l'existance d'une régle ACL
	      if (array_key_exists($role, $this->acl)) {
	        // Existance du scope ?
		      if (array_key_exists($scope, $this->acl[$role])) {
		        // Existance de la régle avec la ressource ?
		        if (array_key_exists($resource, $this->acl[$role][$scope])) {
			        return ($this->acl[$role][$scope][$resource] === true);
		        }
		      }
	      }
	    
	      // Rien de défini : peut-être dans un role parent
	      $parents = $this->roles[$role];
	      if (count($parents) > 0) {
	        // On parcours les parents de manière récursive
		      foreach ($parents as $parent) {
		        if ($this->isAllowed($parent, $resource, $scope)) {
			        return true;
		        }
		      }
	      }
	    }
	
	    // rien de trouvé dans le role ainsi que dans les parents : accès refusé
	    return false;
    }
    
    /**
     * Définir l'acces
     * @param string $role Nom du role
     * @param string $resource Nom de la ressource
     * @param string $scope Le scope
     * @param boolean $access Acces ou deny
     */
    private function setAcl($role, $resource, $scope, $access) {
	    if (!$this->isRoleExist($role)) {
	      trigger_error('ACL : Le role "'.$role.'" n\'existe pas !', E_USER_ERROR);
	    } elseif (!$this->isScopeExist($scope)) {
	      trigger_error('ACL : Le scope "'.$scope.'" n\'existe pas !', E_USER_ERROR);
	    } elseif (!$this->isResourceExist($resource, $scope)) {
	      trigger_error('ACL : La resource "'.$resource.'" n\'existe pas !', E_USER_ERROR);
	    } else {
	      $this->acl[$role][$scope][$resource] = $access;
	    }
    }

    /**
     * Vérifier si un role existe
     * @param string $role Nom du role
     * @return boolean Le role existe ou non
     */
    private function isRoleExist($role) {
	    return array_key_exists($role, $this->roles);
    }
    
    /**
     * Vérifier si un scope existe
     * @param string $scope nom du scope
     * @return boolean Le scope existe ou non
     */
    private function isScopeExist($scope) {
	    return array_key_exists($scope, $this->resources);
    }
    
    /**
     * Vérifier si un role existe
     * @param string $resource Nom de la resource
     * @param string $scope Nom du scope
     * @return boolean La resource existe ou non
     */
    private function isResourceExist($resource, $scope = 'global') {
	    if ($this->isScopeExist($scope)) {
	      return array_key_exists($resource, $this->resources[$scope]);
	    }
	    return false;
    }
    
    /**
     * Affichage des role, ressources, droits<br>
     * A utiliser que pour le débuggage
     * @return string Code HTML
     */
    public function debug() {
	    $html = '<h1>ACL</h1>';
	
	    // Les roles
	    $html .= '<h2>Roles</h2><ul>';
	    foreach ($this->roles as $role => $parents) {
	      $html .= '<li>'.$role;
	      if (count($parents) > 0) {
		      $html .= ' hérite de '.implode(' et ', $parents);
	      }
        $html .= '</li>'; 
	    }
	    $html .= '</ul>';
	
	    // Les ressources
	    $html .= '<h2>Resources</h2><ul>'; 
	    foreach ($this->resources as $scope => $ressources) {
	      $html .= '<li>'.$scope.' : <ul>';
	      foreach ($ressources as $ressource => $v) {
		      $html .= '<li>'.$ressource.'</li>';
        }
	      $html .= '</ul></li>';
	    }
	    $html .= '</ul>';
	
	    // Les droits
	    $html .= '<h2>Droits</h2><ul>'; 
	    foreach ($this->resources as $scope => $ressources) {
  	    $html .= '<li>'.$scope.' : <ul>';
	      foreach ($ressources as $ressource => $v) {
		      $html .= '<li>'.$ressource.' : ';
		      $droits = array();
		      foreach ($this->roles as $role => $v) {
		        $couleur = ($this->isAllowed($role, $ressource, $scope)) ? 'green' : 'red';
		        $droits[] = '<span style="color:'.$couleur.';">'.$role.'</span>';
		      }
		      $html .= implode(', ', $droits);
		      $html .= '</li>';
	      }
	      $html .= '</ul></li>';
	    }
	
	    $html .= '</ul>';
	    return $html;
    }
}
