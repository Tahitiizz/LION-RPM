
/**
 *
 *
 * @author GHX
 */
function displayInfo ( btn, idUl )
{
	if ( $('list-'+idUl).style.display == 'none' )
	{
		btn.src = btn.src.replace(/arrow_right/, 'arrow_down');
		$('list-'+idUl).style.display = 'block';
	}
	else
	{
		btn.src = btn.src.replace(/arrow_down/, 'arrow_right');
		$('list-'+idUl).style.display = 'none';
	}
} // End function displayInfo