import lmdb
import os

data_dir = os.path.abspath(os.path.join(os.path.dirname( __file__ ), '..', 'data/index'))
env = lmdb.open(data_dir, readonly=True)

with env.begin() as txn:
    cursor = txn.cursor()
    for key, value in cursor:
        print(key.decode(), value.decode())

    if txn.get('2'.encode()):
        print("Get: ", txn.get('2'.encode()).decode())
