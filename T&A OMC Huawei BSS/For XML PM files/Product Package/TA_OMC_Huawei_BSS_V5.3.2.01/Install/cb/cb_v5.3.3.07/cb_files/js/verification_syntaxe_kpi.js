
//**************************************************************
// JavaScript functions for checking syntax in the KPI creator *
//**************************************************************
//
// to use these functions, just apply function "checkSyntax" on "textarea.value"
// a result of -1 means "no error"
// another result is the position of the error in the string (indices begin at "0")
// 
// new rules of syntax can be established in the function "checkCrossError"
// 

function performChecking(numerateur,denominateur)
{
	var errorMessage="";
//	numerateur=zone_formule_numerateur;//.value;
//	denominateur=zone_formule_denominateur;//.value;
	errorNum=checkSyntax(numerateur);
	errorDen=checkSyntax(denominateur);
	
	if(errorDen!=-1) errorMessage+="error in the denominator at position : "+errorDen+"\n";
	if(errorNum!=-1) errorMessage+="error in the numerator at position : "+errorNum;
	if((errorDen!=-1)|(errorNum!=-1))
	{
		alert(errorMessage);
		return false;
	} else {
		return true;
	}
	
}

function checkSyntax(expr)	// main function - calls for basic checks
{
	var thereIsAnError = false;
	var firstErrorPosition=expr.length;	// stands for "no error"
	var parenthesisErrorAtPosition;		// number of open parentheses
	var operatorErrorAtPosition;	// operator Error at Position
	var crossErrorAtPosition;	// operator-on-parenthesis Error at Position
	parenthesisErrorAtPosition = check_parentheses(expr);
	operatorErrorAtPosition = check_operators(expr);
	crossErrorAtPosition = checkCrossError(expr);
	
	if(parenthesisErrorAtPosition!=-1){ 
		thereIsAnError=true;
		firstErrorPosition=Math.min(firstErrorPosition,parenthesisErrorAtPosition);
	}
	if(operatorErrorAtPosition!=-1){ 
		thereIsAnError=true;
		firstErrorPosition=Math.min(firstErrorPosition,operatorErrorAtPosition);
	}
	if(crossErrorAtPosition!=-1){ 
		thereIsAnError=true;
		firstErrorPosition=Math.min(firstErrorPosition,crossErrorAtPosition);
	}
	//if((firstErrorPosition!=expr.length)) alert(firstErrorPosition);//debuggung test
	return (firstErrorPosition==expr.length)? -1 : firstErrorPosition;
}


function check_parentheses(expr)	// checks the number of "(" and ")" 
{
	var pos=0;
	var sum=0;
	for(pos=0;pos<expr.length;pos++)
	{
		var character=expr.charAt(pos);
		sum += (character=='(') ? 1 : 0;
		sum -= (character==')') ? 1 : 0;
		if(sum<0) return pos; // sum==-1; you can't have more ")" than "(" at any given point in the equation
	}
	return (sum==0)? parseInt(-1) : pos-1;	// pos-1 to generate an error at the end of the equation
}

function check_operators(expr)	// returns position error if 2 operators follow each other
{
	var operators=new Array("+","-","*","/");
	var pos=0;
	for(pos=0;pos<expr.length-1;pos++)
	{
		var character1=expr.charAt(pos);
		var character2=expr.charAt(pos+1);
		if(inArray(character1,operators) & inArray(character2,operators))
		{
			return parseInt(pos);
		}
	}
	return parseInt(-1);
}


function inArray(myString,myArray){		// true if myString is an element of myArray
	var pos=0;
	for(pos=0;pos<myArray.length;pos++)
	{
		if(myArray[pos]==myString)	return true;
	}
	return false;
}

function checkCrossError(expr)	// returns position of error if error of sequence, -1 if no error
{					// for example : a cross error could be a missing argument between an operator and a ")" symbol
	var operators=new Array("+","-","*","/");
	var numbers=new Array("1","2","3","4","5","6","7","8","9","0");
	//var parenthesis=new Array("(",")");
	var pos=0;
	for(pos=0;pos<expr.length-1;pos++)
	{
		var character1=expr.charAt(pos);
		var character2=expr.charAt(pos+1);
		var char1IsAnOperator = inArray(character1,operators);
		var char2IsAnOperator = inArray(character2,operators);
		var char1IsANumber = inArray(character1,numbers);
		var char2IsANumber = inArray(character2,numbers);
		
		var error1 = (char1IsAnOperator & (character2==")"));
		var error2 = ((character1=="(") & char2IsAnOperator);
		var error3 = ((character1==")") & (character2=="("));	// must be separated by an operator
		var error4 = ((character1=="(") & (character2==")"));	// should it be possible to have nothing between both parentheses???
		var error5 = ((character1==")") & char2IsANumber);
		var error6 = (char1IsANumber & (character2=="("));
		
		if(error1 | error2 | error3 | error4 | error5 | error6) return pos;
	}
	return parseInt(-1);
}