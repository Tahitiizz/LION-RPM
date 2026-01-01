<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
    /**
     * @author Ilya Boyandin <ilyabo@gmx.net>
     */

    function isInstanceOf(&$obj, $className)
    {
        $className = strtolower($className);
        return get_class($obj) == $className  ||  is_subclass_of($obj, $className);
    }

?>
