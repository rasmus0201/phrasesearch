import lmdb

env = lmdb.open('index', readonly=True)

with env.begin() as txn:
    cursor = txn.cursor()
    for key, value in cursor:
        print(key.decode(), value.decode())
