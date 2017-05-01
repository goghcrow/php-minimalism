PHP_ARG_ENABLE(ztrace, whether to enable ae support,
[  --enable-ztrace           Enable ae support])

if test "$PHP_ZTRACE" != "no"; then
  CFLAGS="-std=gnu99 -ggdb"
  PHP_NEW_EXTENSION(ztrace, ztrace.c, $ext_shared)
fi