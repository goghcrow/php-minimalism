PHP_ARG_ENABLE(ae, whether to enable ae support,
[  --enable-ae           Enable ae support])

if test "$PHP_AE" != "no"; then

  PHP_NEW_EXTENSION(ae, 
  	php_ae.c						\
  	ae/ae.c 						\
  	aeUtil.c 						\
  	aeCoroutine.c				\
    aeTcpServer.c       \
    coroutine/coroutine.c       \
    aeEventLoop.c,
  $ext_shared)

  PHP_ADD_BUILD_DIR([$ext_builddir/coroutine])  
  PHP_ADD_BUILD_DIR([$ext_builddir/ae])
fi