red=`tput setaf 9`
green=`tput setaf 10`
yellow=`tput setaf 11`
cyan=`tput setaf 14`
gray=`tput setaf 7`
pink=`tput setaf 13`
reset=`tput sgr0`
if grep -q -E '^(feat|fix|build|style|chore|ci|docs|perf|refactor|revert|test):|^Merged|^merged|Merge' $1
then
    echo "${green}✔ The commit message has been approved, Nice work! ${reset}"; 
    exit 0;
else
    echo "${red}❌ Bad commit message! You've to fix it!${reset}";
    echo "${yellow}The commit message should start with: [feat, fix, refactor, build, chore, ci, docs, , perf, revert, style, test]${reset}";
    echo "${yellow}Examples:${reset}";
    echo "➜ ${green}feat: \\n\\t➜${cyan}Example: ${gray} git commit -m 'feat: ${pink}ID-TICKET-IF-EXIST -${gray} Create new custom module vactory_toto.' ${reset}";
    echo "➜ ${green}fix: \\n\\t➜${cyan}Example: ${gray} git commit -m 'fix: ${pink}ID-TICKET-IF-EXIST -${gray} Bug fix on vactory_toto listing page.' ${reset}";
    echo "➜ ${green}perf: \\n\\t➜${cyan}Example: ${gray} git commit -m 'perf: ${pink}ID-TICKET-IF-EXIST -${gray} Performance Optimisation Backend.' ${reset}";
    echo "➜ ${green}style: \\n\\t➜${cyan}Example: ${gray} git commit -m 'style: ${pink}ID-TICKET-IF-EXIST -${gray} Ckeditor style.' ${reset}";
    echo "➜ ${green}docs: \\n\\t➜${cyan}Example: ${gray} git commit -m 'docs: ${pink}ID-TICKET-IF-EXIST -${gray} Update vactory_toto README.md file.' ${reset}";
    exit 1;
fi