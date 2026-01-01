/************************************************************************************************
méthodes qui permettent la gestion de certains scripts utilisant l'ajax dont le flux ajax doit être annulé (ex : page d'accueil en multiproduit)
*************************************************************************************************/

/**
 * Ajax.Request.abort
 * extend the prototype.js Ajax.Request object so that it supports an abort method
 *
 * PERMET D'ANNULER LA REQUETE AJAX EN COURS
 */
Ajax.Request.prototype.abort = function() {
    // prevent and state change callbacks from being issued
    this.transport.onreadystatechange = Prototype.emptyFunction;
    // abort the XHR
    this.transport.abort();
    // update the request counter
    Ajax.activeRequestCount--;
};

