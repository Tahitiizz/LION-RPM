/*Script permettant le surlignement de la ligne au passage de la souris */
function setPointer(theRow, thePointerColor)
{
    if (typeof(theRow.style) == 'undefined' || typeof(theRow.cells) == 'undefined') {
        return false;
    }
    var row_cells_cnt = theRow.cells.length;
    for (var c = 0; c < row_cells_cnt; c++) {
        theRow.cells[c].bgColor = thePointerColor;
    }
    return true;
}