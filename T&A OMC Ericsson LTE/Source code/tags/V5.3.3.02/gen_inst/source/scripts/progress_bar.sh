#! /bin/bash
# @author SBL
# @date 2011-01-06
# That script shows a progress bar on the screen during installs. It is designed
# to be launched as a background task and checks the status of the PROGRESS_BAR
# variable : when it equals 0, the progress_bar ends.

echo -e "Progress : Each '#' stands for 10 seconds."
echo -n "["
while [[ -e /tmp/progress_bar ]]
do
    sleep 2;echo -en "|"
    sleep 2;echo -en "\b/"
    sleep 2;echo -en "\b-"
    sleep 2;echo -en "\b\\"
    sleep 2;echo -en "\b#"
    
done
echo "]"

exit
 