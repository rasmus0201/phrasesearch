#!/usr/bin/env python3

import csv
import json
import lmdb
import os
import time
from array import array
from io import StringIO
from multiprocessing.connection import Listener
from os import path

address = ('localhost', 6060)

with Listener(address) as listener:
    with listener.accept() as conn:
        print('connection accepted from', listener.last_accepted)

        conn.send([2.25, None, 'junk', float])

        conn.send_bytes(b'hello')

        conn.send_bytes(array('i', [42, 1729]))
