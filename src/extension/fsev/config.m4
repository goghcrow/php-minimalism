PHP_ARG_ENABLE(fsev, 
	[Whether to enable the "fsev" extension],
   	[  --enable-fsev    Enable"fsev" extension support])

# if test $PHP_FSEV !="no"; then
   PHP_SUBST(FSEV_SHARED_LIBADD)
   PHP_NEW_EXTENSION(fsev, fsev.c, $ext_shared)
# fi