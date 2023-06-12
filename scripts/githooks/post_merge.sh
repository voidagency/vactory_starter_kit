# Load ~/.bash_profile & ~/.profile for users which used an alias of composer command.
file="$HOME/.profile"
if [ -a "$file" ]; then
    source $file;
fi
file="$HOME/.bash_profile"
if [ -a "$file" ]; then
    source $file;
fi
composer install;
