/**
 * kazsign_cli.c — Command-line interface for KAZ-SIGN-128 PQC Algorithm
 *
 * Usage:
 *   ./kazsign-cli keygen
 *       → prints: <pubkey_hex>\n<privkey_hex>
 *
 *   ./kazsign-cli sign <privkey_hex> <message_hex>
 *       → prints: <signature_hex>
 *
 *   ./kazsign-cli verify <pubkey_hex> <message_hex> <signature_hex>
 *       → prints: OK  (or exits with non-zero on failure)
 *
 * Build (from kaz-sign-c/ directory in WSL):
 *   gcc -o kazsign-cli kazsign_cli.c kaz_api.o sign.o rng.o \
 *       -I/usr/include -lcrypto -lgmp -lm
 *
 * If .o files not built yet, build them first:
 *   gcc -c sign.c    -o sign.o    -I/usr/include -lcrypto -lgmp
 *   gcc -c kaz_api.c -o kaz_api.o -I/usr/include -lcrypto -lgmp
 *   gcc -c rng.c     -o rng.o     -I/usr/include -lcrypto
 *   gcc -o kazsign-cli kazsign_cli.c kaz_api.o sign.o rng.o \
 *       -I/usr/include -lcrypto -lgmp -lm
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

#include "api.h"
#include "kaz_api.h"
#include "rng.h"

/* ── Hex helpers ─────────────────────────────────────────────────────────── */

static void bytes_to_hex(const unsigned char *buf, size_t len, char *out)
{
    for (size_t i = 0; i < len; i++) {
        sprintf(out + i * 2, "%02x", buf[i]);
    }
    out[len * 2] = '\0';
}

static int hex_to_bytes(const char *hex, unsigned char *out, size_t expected_len)
{
    size_t hex_len = strlen(hex);
    if (hex_len != expected_len * 2) {
        fprintf(stderr, "Error: expected %zu hex chars, got %zu\n",
                expected_len * 2, hex_len);
        return -1;
    }
    for (size_t i = 0; i < expected_len; i++) {
        char byte_str[3] = { hex[i*2], hex[i*2+1], '\0' };
        if (!isxdigit((unsigned char)byte_str[0]) ||
            !isxdigit((unsigned char)byte_str[1])) {
            fprintf(stderr, "Error: invalid hex char at position %zu\n", i*2);
            return -1;
        }
        out[i] = (unsigned char) strtol(byte_str, NULL, 16);
    }
    return 0;
}

/* ── keygen ──────────────────────────────────────────────────────────────── */

static int cmd_keygen(void)
{
    unsigned char pk[CRYPTO_PUBLICKEYBYTES];
    unsigned char sk[CRYPTO_SECRETKEYBYTES];

    init_random();
    int ret = crypto_sign_keypair(pk, sk);
    clear_random();

    if (ret != 0) {
        fprintf(stderr, "keygen failed: %d\n", ret);
        return 1;
    }

    /* Allocate hex buffers */
    char *pk_hex = malloc(CRYPTO_PUBLICKEYBYTES * 2 + 1);
    char *sk_hex = malloc(CRYPTO_SECRETKEYBYTES * 2 + 1);
    if (!pk_hex || !sk_hex) {
        fprintf(stderr, "Memory allocation failed\n");
        free(pk_hex); free(sk_hex);
        return 1;
    }

    bytes_to_hex(pk, CRYPTO_PUBLICKEYBYTES, pk_hex);
    bytes_to_hex(sk, CRYPTO_SECRETKEYBYTES, sk_hex);

    /* Output: line 1 = public key hex, line 2 = private key hex */
    printf("%s\n%s\n", pk_hex, sk_hex);

    free(pk_hex);
    free(sk_hex);
    return 0;
}

/* ── sign ────────────────────────────────────────────────────────────────── */

static int cmd_sign(const char *sk_hex, const char *msg_hex)
{
    /* Decode private key */
    unsigned char sk[CRYPTO_SECRETKEYBYTES];
    if (hex_to_bytes(sk_hex, sk, CRYPTO_SECRETKEYBYTES) != 0) {
        fprintf(stderr, "Invalid private key hex (expected %d bytes = %d hex chars)\n",
                CRYPTO_SECRETKEYBYTES, CRYPTO_SECRETKEYBYTES * 2);
        return 1;
    }

    /* Decode message */
    size_t msg_hex_len = strlen(msg_hex);
    if (msg_hex_len % 2 != 0) {
        fprintf(stderr, "Message hex has odd length\n");
        return 1;
    }
    size_t msg_len = msg_hex_len / 2;
    unsigned char *msg = malloc(msg_len);
    if (!msg) { fprintf(stderr, "Memory allocation failed\n"); return 1; }

    for (size_t i = 0; i < msg_len; i++) {
        char byte_str[3] = { msg_hex[i*2], msg_hex[i*2+1], '\0' };
        msg[i] = (unsigned char) strtol(byte_str, NULL, 16);
    }

    /* Allocate signed message buffer */
    unsigned long long smlen = 0;
    size_t sm_buf_size = KAZ_DS_S1BYTES + KAZ_DS_S2BYTES + KAZ_DS_S3BYTES + msg_len;
    unsigned char *sm = malloc(sm_buf_size);
    if (!sm) {
        fprintf(stderr, "Memory allocation failed\n");
        free(msg);
        return 1;
    }

    init_random();
    int ret = crypto_sign(sm, &smlen, msg, (unsigned long long)msg_len, sk);
    clear_random();

    if (ret != 0) {
        fprintf(stderr, "Signing failed: %d\n", ret);
        free(msg); free(sm);
        return 1;
    }

    /* Output: signature is the first S1+S2+S3 bytes of sm */
    size_t sig_len = KAZ_DS_S1BYTES + KAZ_DS_S2BYTES + KAZ_DS_S3BYTES;
    char *sig_hex  = malloc(sig_len * 2 + 1);
    if (!sig_hex) {
        fprintf(stderr, "Memory allocation failed\n");
        free(msg); free(sm);
        return 1;
    }

    bytes_to_hex(sm, sig_len, sig_hex);
    printf("%s\n", sig_hex);

    free(msg); free(sm); free(sig_hex);
    return 0;
}

/* ── verify ──────────────────────────────────────────────────────────────── */

static int cmd_verify(const char *pk_hex, const char *msg_hex, const char *sig_hex)
{
    /* Decode public key */
    unsigned char pk[CRYPTO_PUBLICKEYBYTES];
    if (hex_to_bytes(pk_hex, pk, CRYPTO_PUBLICKEYBYTES) != 0) {
        fprintf(stderr, "Invalid public key hex\n");
        return 1;
    }

    /* Decode message */
    size_t msg_hex_len = strlen(msg_hex);
    if (msg_hex_len % 2 != 0) {
        fprintf(stderr, "Message hex has odd length\n");
        return 1;
    }
    size_t msg_len = msg_hex_len / 2;
    unsigned char *msg = malloc(msg_len);
    if (!msg) { fprintf(stderr, "Memory allocation failed\n"); return 1; }

    for (size_t i = 0; i < msg_len; i++) {
        char byte_str[3] = { msg_hex[i*2], msg_hex[i*2+1], '\0' };
        msg[i] = (unsigned char) strtol(byte_str, NULL, 16);
    }

    /* Decode signature */
    size_t sig_len = KAZ_DS_S1BYTES + KAZ_DS_S2BYTES + KAZ_DS_S3BYTES;
    size_t sig_hex_len = strlen(sig_hex);
    if (sig_hex_len != sig_len * 2) {
        fprintf(stderr, "Invalid signature length: expected %zu hex chars, got %zu\n",
                sig_len * 2, sig_hex_len);
        free(msg);
        return 1;
    }

    unsigned char *sig = malloc(sig_len);
    if (!sig) {
        fprintf(stderr, "Memory allocation failed\n");
        free(msg);
        return 1;
    }
    hex_to_bytes(sig_hex, sig, sig_len);

    /* Build signed message = sig + msg */
    size_t sm_len = sig_len + msg_len;
    unsigned char *sm = malloc(sm_len);
    if (!sm) {
        fprintf(stderr, "Memory allocation failed\n");
        free(msg); free(sig);
        return 1;
    }
    memcpy(sm, sig, sig_len);
    memcpy(sm + sig_len, msg, msg_len);

    /* Verify */
    unsigned char *recovered_msg = malloc(msg_len);
    unsigned long long recovered_len = 0;

    int ret = crypto_sign_open(recovered_msg, &recovered_len,
                               sm, (unsigned long long)sm_len, pk);

    free(msg); free(sig); free(sm); free(recovered_msg);

    if (ret == 0) {
        printf("OK\n");
        return 0;
    } else {
        printf("INVALID\n");
        return 1;
    }
}

/* ── main ────────────────────────────────────────────────────────────────── */

int main(int argc, char *argv[])
{
    if (argc < 2) {
        fprintf(stderr,
            "KAZ-SIGN CLI — Post-Quantum Digital Signature (Level 128)\n"
            "Algorithm: KAZ-SIGN-128\n\n"
            "Usage:\n"
            "  %s keygen\n"
            "  %s sign   <privkey_hex> <message_hex>\n"
            "  %s verify <pubkey_hex>  <message_hex> <signature_hex>\n\n"
            "Key sizes:\n"
            "  Public key : %d bytes (%d hex chars)\n"
            "  Private key: %d bytes (%d hex chars)\n"
            "  Signature  : %d bytes (%d hex chars)\n",
            argv[0], argv[0], argv[0],
            CRYPTO_PUBLICKEYBYTES,  CRYPTO_PUBLICKEYBYTES  * 2,
            CRYPTO_SECRETKEYBYTES,  CRYPTO_SECRETKEYBYTES  * 2,
            KAZ_DS_S1BYTES + KAZ_DS_S2BYTES + KAZ_DS_S3BYTES,
            (KAZ_DS_S1BYTES + KAZ_DS_S2BYTES + KAZ_DS_S3BYTES) * 2
        );
        return 1;
    }

    const char *cmd = argv[1];

    if (strcmp(cmd, "keygen") == 0) {
        return cmd_keygen();
    }
    else if (strcmp(cmd, "sign") == 0) {
        if (argc < 4) {
            fprintf(stderr, "Usage: %s sign <privkey_hex> <message_hex>\n", argv[0]);
            return 1;
        }
        return cmd_sign(argv[2], argv[3]);
    }
    else if (strcmp(cmd, "verify") == 0) {
        if (argc < 5) {
            fprintf(stderr, "Usage: %s verify <pubkey_hex> <message_hex> <signature_hex>\n", argv[0]);
            return 1;
        }
        return cmd_verify(argv[2], argv[3], argv[4]);
    }
    else {
        fprintf(stderr, "Unknown command: %s\n", cmd);
        fprintf(stderr, "Commands: keygen, sign, verify\n");
        return 1;
    }
}