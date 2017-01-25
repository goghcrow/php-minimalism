#!/usr/bin/env bash

USER_BIN=$HOME/bin
NEW_PATH='export PATH='$USER_BIN':$PATH'

rm -f git_sync
wget http://gitlab.qima-inc.com/delai/GitSync/raw/master/git_sync
mkdir $USER_BIN
mv ./git_sync $USER_BIN
chmod 755 $USER_BIN/git_sync

if [ -f ~/.bashrc ]; then
    grep -q "$NEW_PATH" $HOME/.bashrc || echo "\n$NEW_PATH" >> $HOME/.bashrc
    echo "请手动执行 source ~/.bash_rc"
elif [ -f ~/.bash_profile ]; then
    grep -q "$NEW_PATH" $HOME/.bash_profile || echo "\n$NEW_PATH" >> $HOME/.bash_profile
    echo "请手动执行 source ~/.bash_profile"
elif [ -f ~/.zshrc ]; then
    grep -q "$NEW_PATH" $HOME/.zshrc || echo "\n$NEW_PATH" >> $HOME/.zshrc
    echo "请手动执行 source ~/.zshrc"
elif [ -d /usr/local/bin ]; then
	rm -f /usr/local/bin/git_syc
    ln -s "$USER_BIN/git_syc" /usr/local/bin/git_syc
fi

echo "Done"