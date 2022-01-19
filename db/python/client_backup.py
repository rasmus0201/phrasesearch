#!/usr/bin/env python3

import socket
import json

ClientSocket = socket.socket()
host = '127.0.0.1'
port = 6060

print('Waiting for connection')
try:
    ClientSocket.connect((host, port))
except socket.error as e:
    print(str(e))

response = ClientSocket.recv(1024)
print(response.decode('utf-8'))

request = {
    "action": "insert",
    "documents": "\"1\",\"der er ugler i mosen\"\n\"2\",\"prinsen på den hvide hest\"\r\n"
}

ClientSocket.send(json.dumps(request).encode('utf-8'))
response = ClientSocket.recv(1024)
print(response.decode('utf-8'))


request2 = {
    "action": "delete",
    "keys": ["1"]
}

ClientSocket.send(json.dumps(request2).encode('utf-8'))
response = ClientSocket.recv(1024)
print(response.decode('utf-8'))

request3 = {
    "action": "read",
    "keys": ["1", "2", 3]
}

ClientSocket.send(json.dumps(request3).encode('utf-8'))
response = ClientSocket.recv(1024)
print(response.decode('utf-8'))

request3 = {
    "action": "stats"
}

ClientSocket.send(json.dumps(request3).encode('utf-8'))
response = ClientSocket.recv(1024)
print(response.decode('utf-8'))


ClientSocket.close()
