
CC=gcc
CFLAGS=
INC=-I/usr/include/openssl
LINK=-lcrypto -lgmp -lm

all: PQCgenKAT_sign kazsign-cli

PQCgenKAT_sign: PQCgenKAT_sign.c api.h gmp.h sign.o kaz_api.o rng.o
	$(CC) $(CFLAGS) -o PQCgenKAT_sign PQCgenKAT_sign.c rng.o kaz_api.o sign.o $(INC) $(LINK)

# ── PHP CLI wrapper ───────────────────────────────────────────────────────────
kazsign-cli: kazsign_cli.c sign.o kaz_api.o rng.o api.h kaz_api.h
	$(CC) $(CFLAGS) -o kazsign-cli kazsign_cli.c kaz_api.o sign.o rng.o $(INC) $(LINK)

sign.o: api.h gmp.h kaz_api.h sign.c
	$(CC) $(CFLAGS) -c sign.c -o sign.o $(INC)

kaz_api.o: kaz_api.c kaz_api.h
	$(CC) $(CFLAGS) -c kaz_api.c -o kaz_api.o $(INC)

rng.o: rng.c rng.h
	$(CC) $(CFLAGS) -c rng.c -o rng.o $(INC)

clean:
	rm -f *.o
	rm -f *.req *.rsp
	rm -f main PQCgenKAT_sign kazsign-cli
