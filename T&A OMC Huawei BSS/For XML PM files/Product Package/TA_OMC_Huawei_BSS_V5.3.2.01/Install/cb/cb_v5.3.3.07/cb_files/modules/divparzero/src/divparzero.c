#include <ctype.h>
#include <float.h>
#include <math.h>

#include "postgres.h"
#include <string.h>
#include "fmgr.h"

#ifdef PG_MODULE_MAGIC
PG_MODULE_MAGIC;
#endif



/*
 * check to see if a float4/8 val has underflowed or overflowed
 */
#define CHECKFLOATVAL(val, inf_is_valid, zero_is_valid)                 \
do {                                                                                                                    \
        if (isinf(val) && !(inf_is_valid))                                                      \
                ereport(ERROR,                                                                                  \
                                (errcode(ERRCODE_NUMERIC_VALUE_OUT_OF_RANGE),   \
                  errmsg("value out of range: overflow")));                             \
                                                                                                                                \
        if ((val) == 0.0 && !(zero_is_valid))                                           \
                ereport(ERROR,                                                                                  \
                                (errcode(ERRCODE_NUMERIC_VALUE_OUT_OF_RANGE),   \
                 errmsg("value out of range: underflow")));                             \
} while(0)


PG_FUNCTION_INFO_V1(float8divzero);
Datum
float8divzero(PG_FUNCTION_ARGS)
{
        float8          arg1 = PG_GETARG_FLOAT8(0);
        float8          arg2 = PG_GETARG_FLOAT8(1);
        float8          result;

        if (arg2 == 0.0)
		PG_RETURN_NULL();
        result = arg1 / arg2;

        CHECKFLOATVAL(result, isinf(arg1) || isinf(arg2), arg1 == 0);
        PG_RETURN_FLOAT8(result);
}

PG_FUNCTION_INFO_V1(float48divzero);
Datum
float48divzero(PG_FUNCTION_ARGS)
{
        float4          arg1 = PG_GETARG_FLOAT4(0);
        float8          arg2 = PG_GETARG_FLOAT8(1);
        float8          result;

        if (arg2 == 0.0)
		PG_RETURN_NULL();

        result = arg1 / arg2;
        CHECKFLOATVAL(result, isinf(arg1) || isinf(arg2), arg1 == 0);
        PG_RETURN_FLOAT8(result);
}

PG_FUNCTION_INFO_V1(float84divzero);
Datum
float84divzero(PG_FUNCTION_ARGS)
{
        float8          arg1 = PG_GETARG_FLOAT8(0);
        float4          arg2 = PG_GETARG_FLOAT4(1);
        float8          result;

        if (arg2 == 0.0)
		PG_RETURN_NULL();

        result = arg1 / arg2;

        CHECKFLOATVAL(result, isinf(arg1) || isinf(arg2), arg1 == 0);
        PG_RETURN_FLOAT8(result);
}

PG_FUNCTION_INFO_V1(float4divzero);
Datum
float4divzero(PG_FUNCTION_ARGS)
{
        float4          arg1 = PG_GETARG_FLOAT4(0);
        float4          arg2 = PG_GETARG_FLOAT4(1);
        float4          result;

        if (arg2 == 0.0)
		PG_RETURN_NULL();

        result = arg1 / arg2;

        CHECKFLOATVAL(result, isinf(arg1) || isinf(arg2), arg1 == 0);
        PG_RETURN_FLOAT4(result);
}
