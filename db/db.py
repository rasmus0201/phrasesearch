#!/usr/bin/env python3

import lmdb
import socket
import os
from os import path
from io import StringIO
from _thread import *
import base64
import csv

ServerSocket = socket.socket()
host = '127.0.0.1'
port = 6060
ThreadCount = 0

try:
    ServerSocket.bind((host, port))
except socket.error as e:
    print(str(e))

print('Waiting for a connection..')
ServerSocket.listen(5)

def threaded_client(connection):
    connection.send(str.encode('Welcome to the Servern'))
    while True:
        data = connection.recv(4096)
        if not data:
            break

        map_size=(1024 * 1024 * 1024) * 4 # 1GB * x
        data_dir = os.path.abspath(os.path.join(os.path.dirname( __file__ ), '..', 'data/index'))
        env = lmdb.open(data_dir, map_size=map_size)

        print(env.stat())

        fh = StringIO(base64.b64decode(data).decode())
        reader = csv.reader(fh)

        with env.begin(write=True) as txn:
            for row in reader:
                (key, value) = row
                txn.put(key.encode(), value.encode())

        connection.sendall(str.encode("done"))

    connection.close()


while True:
    Client, address = ServerSocket.accept()
    print('Connected to: ' + address[0] + ':' + str(address[1]))
    start_new_thread(threaded_client, (Client, ))
    ThreadCount += 1
    print('Thread Number: ' + str(ThreadCount))

ServerSocket.close()
