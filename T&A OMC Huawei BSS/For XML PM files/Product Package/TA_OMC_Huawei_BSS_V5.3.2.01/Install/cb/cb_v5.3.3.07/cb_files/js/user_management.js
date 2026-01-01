function validate_user()
	{
	if (info_user.username_mngt.value=="")
		{
		 alert('Fill in the field \'Name\'');
		 return false;
		}
	// 23/02/2012 NSE DE Astellia Portal Lot2
        // suppression du test sur le prénom
         
	if (info_user.login_mngt.value=="")
		{
		 alert('Fill in the field \'Login\'');
		 return false;
		}			
		
	if (info_user.password_mngt.value=="")
		{
		 alert('Fill in the field \'Password\'');
		 return false;
		}			
		
	if (info_user.confirm_password_mngt.value=="")
		{
		 alert('Confirm the \'Password\'');
		 return false;
		}			
	if (info_user.confirm_password_mngt.value!=info_user.password_mngt.value)
		{
		 alert('The password and its confirmation are not the same');
		 info_user.confirm_password_mngt.value="";
		 info_user.confirm_password_mngt.focus();
		 return false;
		}			
	if (info_user.agregation_network.value==-1)
		{
		 alert('Select a Network Aggregation');
		 return false;
		}			
		
	if (info_user.agregation_network_value.value==-1)
		{
		 alert('Select a Network Aggregation value');
		 return false;
		}			
		
	if (info_user.user_profil.value==-1)
		{
		 alert('Select a Profile');
		 return false;
		}			
		
		
	}
