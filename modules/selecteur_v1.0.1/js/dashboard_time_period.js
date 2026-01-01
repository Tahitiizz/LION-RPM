/**
 * Cette fonction force une valeur correcte sur le champ period lorsqu'on le quite
 * Elle est appelée par $('selecteur_period').onblur
 *
 *
 * @author  SLC - 26/09/2008
 * @version CB 4.1.0.0
 * @since   CB 4.1.0.0
 * @params  void part de $F('selecteur_period')
 * @return  void renseigne la valeur de $('selecteur_period')
 */

/**
 * Fonction appellée à la perte de focus du champ période
 *
 * 14/02/2011 OJT : DE Sélecteur. Gestion de la valeur max dynamique
 */
function selecteurPeriodBlur()
{
    var nbPeriodWarn = 200; // Seuil avant l'affichage du WARNING
    var period = parseInt($F('selecteur_period'),10);

    // 26/11/2012 NSE bz 30059 (reopen) : le parseInt fait perdre le caractère spécial. 
    // On met donc à jour le sélecteur avec cette nouvelle valeur si besoin.
    if (period != $('selecteur_period').value){
        $('selecteur_period').value	= period;
    } 
    
    // 24/10/2012 BBX
    // BZ 30059 : on interdit tous les caractères non entiers positifs
    intExp = new RegExp("^[0-9]+$");
    if((!intExp.test(period)) || (period <= 0)) {
        period = selecteur_max_period;
        rouge('selecteur_period',1);
    }
    
    if ( period > selecteur_max_period )
    {
        period = selecteur_max_period;
        rouge('selecteur_period',period);
    }

    if( period > nbPeriodWarn )
    {
        $( 'selecteur_period_div_warn' ).show();
    }
    else
    {
        $( 'selecteur_period_div_warn' ).hide();
    }
}

// On initialise l'event et on effectue un premier appel
$('selecteur_period').onblur = selecteurPeriodBlur;
selecteurPeriodBlur();
