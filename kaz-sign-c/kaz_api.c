#include <stdio.h>
#include <stdlib.h>
#include <stdbool.h>
#include <string.h>
#include <time.h>
#include <math.h>
#include <openssl/evp.h>

#include "api.h"
#include "gmp.h"
#include "kaz_api.h"

// Declare global random state
gmp_randstate_t state;

// Initialize once
void init_random() {
    //struct timespec ts;
    //clock_gettime(CLOCK_REALTIME, &ts);

    // Combine sec + nsec into a seed
    //unsigned long seed = (unsigned long) ts.tv_nsec ^ (unsigned long) ts.tv_sec;

    //gmp_randinit_default(state);
    //gmp_randseed_ui(state, seed);
	unsigned long seed = 123456789UL;   // fixed seed for repeatability
    gmp_randinit_default(state);
    gmp_randseed_ui(state, seed);
}

// Cleanup once at the end
void clear_random() {
    gmp_randclear(state);
}

void KAZ_SIGN_RANDOM(mpz_t lb, mpz_t ub, mpz_t out)
{
	mpz_t range, rand_in_range;
    mpz_inits(range, rand_in_range, NULL);

    // range = ub - lb + 1
    mpz_sub(range, ub, lb);
    mpz_add_ui(range, range, 1);

    // rand_in_range âˆˆ [0, range-1]
    mpz_urandomm(rand_in_range, state, range);

    // out = lb + rand_in_range
    mpz_add(out, lb, rand_in_range);

    mpz_clears(range, rand_in_range, NULL);
}

void HashMsg(const unsigned char *msg, unsigned int mlen, unsigned char buf[CRYPTO_BYTES])
{
    // Initialize the digest context and compute the hash
	EVP_MD_CTX *mdctx = EVP_MD_CTX_new();
    const EVP_MD *md = EVP_sha256();

    EVP_DigestInit_ex(mdctx, md, NULL);
    EVP_DigestUpdate(mdctx, msg, mlen);
    EVP_DigestFinal_ex(mdctx, buf, &mlen);

    // Clean up
    EVP_MD_CTX_free(mdctx);
}

void KAZ_DS_KEYGEN(unsigned char *kaz_ds_verify_key, unsigned char *kaz_ds_sign_key)
{
    mpz_t g1, g2, N, phiN, Og1N, Og2N;
    mpz_t tmp, lowerbound, upperbound, V, s, t;

    mpz_inits(g1, g2, N, phiN, Og1N, Og2N, NULL);
    mpz_inits(tmp, lowerbound, upperbound, V, s, t, NULL);

    //load system parameters and precomputed parameters
    mpz_set_str(g1, KAZ_DS_SP_g1, 10);
    mpz_set_str(g2, KAZ_DS_SP_g2, 10);
    mpz_set_str(N, KAZ_DS_SP_N, 10);
	mpz_set_str(phiN, KAZ_DS_SP_phiN, 10);
	mpz_set_str(Og1N, KAZ_DS_SP_Og1N, 10);
	mpz_set_str(Og2N, KAZ_DS_SP_Og2N, 10);

	int LOg1N=mpz_sizeinbase(Og1N, 2);
	int LOg2N=mpz_sizeinbase(Og2N, 2);
	
	//1) Generate random s,  t
	mpz_ui_pow_ui(lowerbound, 2, LOg1N-2);
	mpz_set(upperbound, Og1N);
	KAZ_SIGN_RANDOM(lowerbound, upperbound, s);

	mpz_ui_pow_ui(lowerbound, 2, LOg2N-2);
	mpz_set(upperbound, Og2N);
	KAZ_SIGN_RANDOM(lowerbound, upperbound, t);
	
	//2) Compute V
	mpz_powm(tmp, g1, s, N);
	mpz_powm(V, g2, t, N);
	mpz_mul(V, V, tmp);
	mpz_mod(V, V, N);

    //3) Set kaz_ds_sign_key=(s,t) & kaz_ds_verify_key=(V)
    size_t SSIZE=mpz_sizeinbase(s, 16);
	size_t TSIZE=mpz_sizeinbase(t, 16);
	size_t VSIZE=mpz_sizeinbase(V, 16);

	unsigned char* SBYTE=(unsigned char*) malloc(SSIZE*sizeof(unsigned char));
	unsigned char* TBYTE=(unsigned char*) malloc(TSIZE*sizeof(unsigned char));
	unsigned char* VBYTE=(unsigned char*) malloc(VSIZE*sizeof(unsigned char));
	
	if (!SBYTE || !TBYTE || !VBYTE) {
        fprintf(stderr, "KAZ-SIGN-KEYGEN: Memory allocation failed.\n");
		printf("KAZ-SIGN-KEYGEN: Memory allocation failed.\n");
		goto cleanup;
    }
	
	mpz_export(SBYTE, &SSIZE, 1, sizeof(char), 0, 0, s);
	mpz_export(TBYTE, &TSIZE, 1, sizeof(char), 0, 0, t);
	mpz_export(VBYTE, &VSIZE, 1, sizeof(char), 0, 0, V);
	
	// Clear output keys
    memset(kaz_ds_sign_key, 0, CRYPTO_SECRETKEYBYTES);
    memset(kaz_ds_verify_key, 0, CRYPTO_PUBLICKEYBYTES);

	/*memcpy(kaz_ds_sign_key, SBYTE, SSIZE);
	memcpy(kaz_ds_sign_key + SSIZE, TBYTE, TSIZE);

	memcpy(kaz_ds_verify_key, VBYTE, VSIZE);*/

	int je=CRYPTO_SECRETKEYBYTES-1;
	
	for(int i=TSIZE-1; i>=0; i--){
		kaz_ds_sign_key[je]=TBYTE[i];
		je--;
	}

	je=CRYPTO_SECRETKEYBYTES-KAZ_DS_TBYTES-1;
	for(int i=SSIZE-1; i>=0; i--){
		kaz_ds_sign_key[je]=SBYTE[i];
		je--;
	}
	
	je=CRYPTO_PUBLICKEYBYTES-1;
	for(int i=VSIZE-1; i>=0; i--){
		kaz_ds_verify_key[je]=VBYTE[i];
		je--;
	}
	
	cleanup:		
		mpz_clears(g1, g2, N, phiN, Og1N, Og2N, NULL);
    	mpz_clears(tmp, lowerbound, upperbound, V, s, t, NULL);

		if (SBYTE) {
			memset(SBYTE, 0, SSIZE);
			free(SBYTE);
		}
		if (TBYTE) {
			memset(TBYTE, 0, TSIZE);
			free(TBYTE);
		}
		if (VBYTE) {
			memset(VBYTE, 0, VSIZE);
			free(VBYTE);
		}
}

int KAZ_DS_SIGNATURE(unsigned char *sign, 
	                 unsigned long long *signlen, 
					 const unsigned char *m, 
					 unsigned long long mlen, 
					 const unsigned char *sk)
{
    mpz_t g1, g2, N, phiN, Og1N, Og2N, s, t;
    mpz_t tmp, lowerbound, upperbound, e1, e2, h, S1, S2, S3;

    mpz_inits(g1, g2, N, phiN, Og1N, Og2N, s, t, NULL);
    mpz_inits(tmp, lowerbound, upperbound, e1, e2, h, S1, S2, S3, NULL);
	
    // load system parameters and precomputed parameters
	mpz_set_str(g1, KAZ_DS_SP_g1, 10);
    mpz_set_str(g2, KAZ_DS_SP_g2, 10);
    mpz_set_str(N, KAZ_DS_SP_N, 10);
	mpz_set_str(phiN, KAZ_DS_SP_phiN, 10);
	mpz_set_str(Og1N, KAZ_DS_SP_Og1N, 10);
	mpz_set_str(Og2N, KAZ_DS_SP_Og2N, 10);

	int LOg1N=mpz_sizeinbase(Og1N, 2);
	int LOg2N=mpz_sizeinbase(Og2N, 2);

    //1) Get kaz_ds_sign_key=(s, t)
	unsigned char *SBYTE=NULL;
	unsigned char *TBYTE=NULL;
	
	SBYTE=(unsigned char*) malloc((KAZ_DS_SBYTES)*sizeof(unsigned char));
	TBYTE=(unsigned char*) malloc((KAZ_DS_TBYTES)*sizeof(unsigned char));
	
	if (!SBYTE || !TBYTE) {
        fprintf(stderr, "KAZ-SIGN-SIGNATURE: Memory allocation failed.\n");
		printf("KAZ-SIGN-SIGNATURE: Memory allocation failed.\n");
		goto cleanup;
    }
	
	// Clear output keys
    memset(SBYTE, 0, KAZ_DS_SBYTES);
    memset(TBYTE, 0, KAZ_DS_TBYTES);

	//memcpy(SBYTE, sk, KAZ_DS_SBYTES);
	//memcpy(TBYTE, sk + KAZ_DS_SBYTES, KAZ_DS_TBYTES);

	for(int i=0; i<KAZ_DS_SBYTES; i++){SBYTE[i]=sk[i];}
	for(int i=0; i<KAZ_DS_TBYTES; i++){TBYTE[i]=sk[i+KAZ_DS_SBYTES];}
	
	mpz_import(s, KAZ_DS_SBYTES, 1, sizeof(char), 0, 0, SBYTE);
	mpz_import(t, KAZ_DS_TBYTES, 1, sizeof(char), 0, 0, TBYTE);

	//2) Compute HASHValue(m)
	unsigned char buf[CRYPTO_BYTES];
	HashMsg(m, mlen, buf);
	mpz_import(h, CRYPTO_BYTES, 1, sizeof(char), 0, 0, buf);

	//3) Generate random e1,  e2
	mpz_ui_pow_ui(lowerbound, 2, LOg1N-2);
	mpz_set(upperbound, Og1N);
	KAZ_SIGN_RANDOM(lowerbound, upperbound, e1);
	mpz_nextprime(e1, e1);

	mpz_ui_pow_ui(lowerbound, 2, LOg2N-2);
	mpz_set(upperbound, Og2N);
	KAZ_SIGN_RANDOM(lowerbound, upperbound, e2);

	//4) Compute S1,  S2, S3
	mpz_powm(tmp, g1, e1, N);
	mpz_powm(S1, g2, e2, N);
	mpz_mul(S1, S1, tmp);
	mpz_mod(S1, S1, N);

	mpz_set_ui(tmp, 0);
	mpz_mul(tmp, s, S1);
	mpz_mod(tmp, tmp, phiN);
	mpz_sub(tmp, h, tmp);
	mpz_mod(tmp, tmp, phiN);
	mpz_invert(S2, e1, phiN);
	mpz_mul(S2, tmp, S2);
	mpz_mod(S2, S2, phiN);

	mpz_set_ui(tmp, 0);
	mpz_mul(tmp, e2, S2);
	mpz_mod(tmp, tmp, phiN);
	mpz_mul(S3, t, S1);
	mpz_mod(S3, S3, phiN);
	mpz_sub(S3, h, S3);
	mpz_mod(S3, S3, phiN);
	mpz_sub(S3, S3, tmp);
	mpz_mod(S3, S3, phiN);
		
    //5) Set signature=(S1, S2, S3, m)
    size_t S1SIZE=mpz_sizeinbase(S1, 16);
	size_t S2SIZE=mpz_sizeinbase(S2, 16);
	size_t S3SIZE=mpz_sizeinbase(S3, 16);
	
	unsigned char* S1BYTE=(unsigned char*) malloc(S1SIZE*sizeof(unsigned char));
	unsigned char* S2BYTE=(unsigned char*) malloc(S2SIZE*sizeof(unsigned char));
	unsigned char* S3BYTE=(unsigned char*) malloc(S3SIZE*sizeof(unsigned char));
	
	memset(S1BYTE, 0, KAZ_DS_S1BYTES);
	memset(S2BYTE, 0, KAZ_DS_S2BYTES);
	memset(S3BYTE, 0, KAZ_DS_S3BYTES);
	
	mpz_export(S1BYTE, &S1SIZE, 1, sizeof(char), 0, 0, S1);
	mpz_export(S2BYTE, &S2SIZE, 1, sizeof(char), 0, 0, S2);
	mpz_export(S3BYTE, &S3SIZE, 1, sizeof(char), 0, 0, S3);
	
	// Clear output signature
	memset(sign, 0, KAZ_DS_S1BYTES+KAZ_DS_S2BYTES+KAZ_DS_S3BYTES+mlen);

	/*memcpy(sign, S1BYTE, S1SIZE);
	memcpy(sign + S1SIZE, S2BYTE, S2SIZE);
	memcpy(sign + S1SIZE + S2SIZE, S3BYTE, S3SIZE);
	memcpy(sign + S1SIZE + S2SIZE + S3SIZE, m, mlen);*/

	int je=mlen+KAZ_DS_S3BYTES+KAZ_DS_S2BYTES+KAZ_DS_S1BYTES-1;
	for(int i=mlen-1; i>=0; i--){
		sign[je]=m[i];
		je--;
	}

	je=KAZ_DS_S3BYTES+KAZ_DS_S2BYTES+KAZ_DS_S1BYTES-1;
	for(int i=S3SIZE-1; i>=0; i--){
		sign[je]=S3BYTE[i];
		je--;
	}

	je=KAZ_DS_S2BYTES+KAZ_DS_S1BYTES-1;
	for(int i=S2SIZE-1; i>=0; i--){
		sign[je]=S2BYTE[i];
		je--;
	}
	
	je=KAZ_DS_S1BYTES-1;
	for(int i=S1SIZE-1; i>=0; i--){
		sign[je]=S1BYTE[i];
		je--;
	}

	*signlen=KAZ_DS_S1BYTES+KAZ_DS_S2BYTES+KAZ_DS_S3BYTES+mlen;
	
	cleanup:
		mpz_clears(g1, g2, N, phiN, Og1N, Og2N, s, t, NULL);
    	mpz_clears(tmp, lowerbound, upperbound, e1, e2, h, S1, S2, S3, NULL);

		if (S1BYTE) {
			memset(S1BYTE, 0, KAZ_DS_S1BYTES);
			free(S1BYTE);
		}
		if (S2BYTE) {
			memset(S2BYTE, 0, KAZ_DS_S2BYTES);
			free(S2BYTE);
		}
		if (S3BYTE) {
			memset(S3BYTE, 0, KAZ_DS_S3BYTES);
			free(S3BYTE);
		}
		if (SBYTE) {
			memset(SBYTE, 0, KAZ_DS_SBYTES);
			free(SBYTE);
		}
		if (TBYTE) {
			memset(TBYTE, 0, KAZ_DS_TBYTES);
			free(TBYTE);
		}

	return 0;
}

int KAZ_DS_VERIFICATION(unsigned char *m, 
	                    unsigned long long *mlen, 
						const unsigned char *sm, 
						unsigned long long smlen, 
						const unsigned char *pk)
{
    mpz_t g1, g2, N, V, S1, S2, S3;
    mpz_t tmp, h, Y1, Y2;

    mpz_inits(g1, g2, N, V, S1, S2, S3, NULL);
    mpz_inits(tmp, h, Y1, Y2, NULL);
	
    // load system parameters and precomputed parameters
	mpz_set_str(g1, KAZ_DS_SP_g1, 10);
    mpz_set_str(g2, KAZ_DS_SP_g2, 10);
    mpz_set_str(N, KAZ_DS_SP_N, 10);
	
    //1) Get kaz_ds_verify_key=(V)
	mpz_import(V, KAZ_DS_VBYTES, 1, sizeof(char), 0, 0, pk);

    //2) Get signature=(S1, S2, S3, m)
	int len=smlen-(KAZ_DS_S1BYTES+KAZ_DS_S2BYTES+KAZ_DS_S3BYTES);
    unsigned char* S1BYTE=(unsigned char*) malloc((KAZ_DS_S1BYTES)*sizeof(unsigned char));
	unsigned char* S2BYTE=(unsigned char*) malloc((KAZ_DS_S2BYTES)*sizeof(unsigned char));
	unsigned char* S3BYTE=(unsigned char*) malloc((KAZ_DS_S3BYTES)*sizeof(unsigned char));
	unsigned char* MBYTE=(unsigned char*) malloc(len*sizeof(unsigned char));
	
	if (!S1BYTE || !S2BYTE || !S3BYTE || !MBYTE) {
        fprintf(stderr, "KAZ-SIGN-VERIFICATION: Memory allocation failed.\n");
		printf("KAZ-SIGN-VERIFICATION: Memory allocation failed.\n");
		goto cleanup;
    }
	
	// Clear output signature
	memset(S1BYTE, 0, KAZ_DS_S1BYTES);
	memset(S2BYTE, 0, KAZ_DS_S2BYTES);
	memset(S3BYTE, 0, KAZ_DS_S3BYTES);
	memset(MBYTE, 0, len);
   
	//unsigned int S2=(unsigned int)((sm[KAZ_DS_SBYTES-2]) << 8) | (sm[KAZ_DS_SBYTES-1]);
	for(int i=0; i<KAZ_DS_S1BYTES; i++){S1BYTE[i]=sm[i];}
	for(int i=0; i<KAZ_DS_S2BYTES; i++){S2BYTE[i]=sm[i+KAZ_DS_S1BYTES];}
	for(int i=0; i<KAZ_DS_S3BYTES; i++){S3BYTE[i]=sm[i+KAZ_DS_S1BYTES+KAZ_DS_S2BYTES];}
	for(int i=0; i<len; i++){MBYTE[i]=sm[i+KAZ_DS_S1BYTES+KAZ_DS_S2BYTES+KAZ_DS_S3BYTES];}

	/*memcpy(S1BYTE, sm, KAZ_DS_S1BYTES);
    memcpy(S2BYTE, sm + KAZ_DS_S1BYTES, KAZ_DS_S2BYTES);
    memcpy(S3BYTE, sm + KAZ_DS_S1BYTES + KAZ_DS_S2BYTES, KAZ_DS_S3BYTES);
    memcpy(MBYTE, sm + KAZ_DS_S1BYTES + KAZ_DS_S2BYTES + KAZ_DS_S3BYTES, 32);*/

    mpz_import(S1, KAZ_DS_S1BYTES, 1, sizeof(char), 0, 0, S1BYTE);
	mpz_import(S2, KAZ_DS_S2BYTES, 1, sizeof(char), 0, 0, S2BYTE);
	mpz_import(S3, KAZ_DS_S3BYTES, 1, sizeof(char), 0, 0, S3BYTE);
        
	//3) Compute the hash value of the message
    unsigned char buf[CRYPTO_BYTES];
	HashMsg(MBYTE, len, buf);
	mpz_import(h, CRYPTO_BYTES, 1, sizeof(char), 0, 0, buf);

    //4) Verifying Procedures
	mpz_powm(tmp, V, S1, N);
    mpz_powm(Y1, S1, S2, N);
	mpz_mul(Y1, tmp, Y1);
	mpz_mod(Y1, Y1, N);
	mpz_set_ui(tmp, 0);
	mpz_powm(tmp, g2, S3, N);
	mpz_mul(Y1, tmp, Y1);
	mpz_mod(Y1, Y1, N);

	mpz_set_ui(tmp, 0);
	mpz_mul(tmp, g1, g2);
	mpz_powm(Y2, tmp, h, N);

    if(mpz_cmp(Y1, Y2)!=0){
		printf("Invalid Signature...\n");
        return -4;
	}
	
    memcpy(m, MBYTE, len);
    *mlen=len;

	cleanup:
		mpz_clears(g1, g2, N, V, S1, S2, S3, NULL);
    	mpz_clears(tmp, Y1, Y2, NULL);
		
		memset(buf, 0, CRYPTO_BYTES);
		free(S1BYTE);
		free(S2BYTE);
		free(S3BYTE);
		free(MBYTE);

	return 0;
}