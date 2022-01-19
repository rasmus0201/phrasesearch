#!/usr/bin/env python3

import lmdb
import socket
import os
from os import path
from io import StringIO
from _thread import *
import csv
import time
import json

serverSocket = socket.socket()
host = "127.0.0.1"
port = 6060

try:
    serverSocket.bind((host, port))
except socket.error as e:
    print(str(e))

map_size=(1024 * 1024 * 1024) * 4 # 1GB * x
data_dir = os.path.abspath(os.path.join(os.path.dirname( __file__ ), "..", "data/index"))
env = lmdb.open(data_dir, map_size=map_size)

print("Waiting for a connection...")
serverSocket.listen(128)

def recv_json(socket,timeout=2):
    socket.setblocking(0)
    return json.loads(b"".join(socket.recv(8192)).decode("utf-8"))

    socket.setblocking(0)

    total_data = []
    data = b""

    begin = time.time()
    while True:
        # If you got some data, then break after timeout
        if total_data and (time.time() - begin) > timeout:
            break

        # If you got no data at all, wait a little longer, twice the timeout
        elif (time.time() - begin) > (timeout * 2):
            break

        try:
            data = socket.recv(8192)
            if data:
                total_data.append(data)
                begin = time.time()
            else:
                time.sleep(0.1)
        except:
            pass

    return json.loads(b''.join(total_data).decode("utf-8"))

def threaded_client(connection, env):
    while True:
        data = recv_json(connection)
        print(data)

        if data["action"] == "insert":
            reader = csv.reader(StringIO(data["documents"]))

            with env.begin(write=True) as txn:
                for row in reader:
                    (key, value) = row
                    txn.put(str(key).encode(), value.encode())
        elif data["action"] == "remove":
            with env.begin(write=True) as txn:
                for key in data["keys"]:
                    txn.delete(str(key).encode())
        elif data["action"] == "read":
            documents = {}
            with env.begin(write=False, buffers=True) as txn:
                for key in data["keys"]:
                    value = txn.get(str(key).encode())
                    documents[key] = value.decode() if value else None

            connection.sendall(str.encode(json.dumps(documents)))
        elif data["action"] == "stats":
            connection.sendall(str.encode(json.dumps(env.stat())))

        try:
            connection.sendall(str.encode("done"))
        except:
            break

    connection.close()

while True:
    client, address = serverSocket.accept()
    print("Connected to: " + address[0] + ":" + str(address[1]))
    start_new_thread(threaded_client, (client, env))


serverSocket.close()
