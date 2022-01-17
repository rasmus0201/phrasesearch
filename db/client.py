#!/usr/bin/env python3

import socket
import base64

ClientSocket = socket.socket()
host = '127.0.0.1'
port = 6060

print('Waiting for connection')
try:
    ClientSocket.connect((host, port))
except socket.error as e:
    print(str(e))

response = ClientSocket.recv(1024)
csv = "\"1\",\"der er ugler i mosen\"\n\"2\",\"prinsen på den hvide hest\"\n"

ClientSocket.send(base64.b64encode(csv.encode('utf-8')))
response = ClientSocket.recv(1024)
print(response.decode('utf-8'))


ClientSocket.close()
