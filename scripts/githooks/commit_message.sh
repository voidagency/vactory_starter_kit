red=`tput setaf 9`
green=`tput setaf 10`
yellow=`tput setaf 11`
cyan=`tput setaf 14`
gray=`tput setaf 7`
pink=`tput setaf 13`
reset=`tput sgr0`
if grep -q -E '^(Add|Fix|Change|Update|Documentation):|^Merged|^merged|Merge' $1
then
    echo "${green}✔ The commit message has been approved, Nice work! ${reset}"; 
    exit 0;
else
    echo "${red}❌ Bad commit message! You've to fix it!${reset}";
    echo "${yellow}The commit message should start with:${reset}";
    echo "➜ ${green}Add: \\n\\t➜${cyan}Example: ${gray} git commit -m 'Add: ${pink}ID-TICKET-IF-EXIST -${gray} Create new custom module vactory_toto.' ${reset}";
    echo "➜ ${green}Fix: \\n\\t➜${cyan}Example: ${gray} git commit -m 'Fix: ${pink}ID-TICKET-IF-EXIST -${gray} Bug fix on vactory_toto listing page.' ${reset}";
    echo "➜ ${green}Change: \\n\\t➜${cyan}Example: ${gray} git commit -m 'Change: ${pink}ID-TICKET-IF-EXIST -${gray} Replace agencies CSV import file.' ${reset}";
    echo "➜ ${green}Update: \\n\\t➜${cyan}Example: ${gray} git commit -m 'Update: ${pink}ID-TICKET-IF-EXIST -${gray} Core security update to 9.0.3.' ${reset}";
    echo "➜ ${green}Documentation: \\n\\t➜${cyan}Example: ${gray} git commit -m 'Documentation: ${pink}ID-TICKET-IF-EXIST -${gray} Update vactory_toto README.md file.' ${reset}";
    exit 1;
fi