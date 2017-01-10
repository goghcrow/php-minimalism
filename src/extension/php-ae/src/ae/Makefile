# http://www.ruanyifeng.com/blog/2015/02/make.html

uname_S := $(shell sh -c 'uname -s 2>/dev/null || echo not')
# OPTIMIZATION?=-O0
OPTIMIZATION?=-O0

STD=-std=c99 -pedantic -DREDIS_STATIC=''
WARN=-Wall -W -Wno-missing-field-initializers
OPT=$(OPTIMIZATION)
DEBUG=-g -ggdb

FINAL_CFLAGS=$(STD) $(WARN) $(OPT) $(DEBUG) $(CFLAGS)
FINAL_LDFLAGS=$(LDFLAGS) $(DEBUG)
FINAL_LIBS=-lm

ifeq ($(uname_S),Darwin)
	# Darwin
	FINAL_LIBS+= -ldl
else
	# All the other OSes (notably Linux)
	FINAL_LDFLAGS+= -rdynamic
	FINAL_LIBS+=-ldl -pthread
endif

_CC=$(QUIET_CC)$(CC) $(FINAL_CFLAGS)
_LD=$(QUIET_LINK)$(CC) $(FINAL_LDFLAGS)

SERVER_NAME=aetest
SERVER_OBJ=test.o ae.o anet.o

# $(SERVER_NAME): $(SERVER_OBJ)
	# $(_LD) -o $@ $^ ../xx.a $(FINAL_LIBS)

$(SERVER_NAME): $(SERVER_OBJ)
	$(_CC) -o $@ $^

%.o: %.c .make-prerequisites
	$(_CC) -c $<

.make-prerequisites:
	@touch $@

.PHONY: clean
clean:
	rm -rf *.o