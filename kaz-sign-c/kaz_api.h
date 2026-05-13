#ifndef FILE_H_INCLUDED
#define FILE_H_INCLUDED
#include "gmp.h"
// LIST OF PRECOMPUTED SYSTEM PARAMETERS

#define KAZ_DS_SP_J				128

#define KAZ_DS_SP_g1			"65537"
#define KAZ_DS_SP_g2			"65539"

#define KAZ_DS_SP_N				"9680693320350411581735712527156160041331448806285781880953481207107506184928318589548473667621840334803765737814574120142199988285"
#define KAZ_DS_SP_phiN			"1862854061641389163337017925599133865006616816206541406153748908271169581801631840410608441366518309266967756800000000000000000000"

#define KAZ_DS_SP_Og1N			"104096837085595768062256170741230052000"
#define KAZ_DS_SP_Og2N			"17349472847599294677042695123538342000"

#define KAZ_DS_VBYTES			54
#define KAZ_DS_SBYTES			16
#define KAZ_DS_TBYTES			16
#define KAZ_DS_S1BYTES			54
#define KAZ_DS_S2BYTES			54
#define KAZ_DS_S3BYTES			54

extern void init_random();
extern void clear_random();
extern void KAZ_SIGN_RANDOM(mpz_t lb, mpz_t ub, mpz_t out);

extern void KAZ_DS_KEYGEN(unsigned char *kaz_ds_verify_key,
                          unsigned char *kaz_ds_sign_key);

extern int KAZ_DS_SIGNATURE(unsigned char *signature,
                             unsigned long long *signlen,
                             const unsigned char *m,
                             unsigned long long mlen,
                             const unsigned char *kaz_ds_sign_key);

extern int KAZ_DS_VERIFICATION(unsigned char *m,
                               unsigned long long *mlen,
							   const unsigned char *sm,
                               unsigned long long smlen,
                               const unsigned char *kaz_ds_verify_key);

#endif // FILE_H_INCLUDED