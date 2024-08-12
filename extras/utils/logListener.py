#!/usr/bin/python

#Used for remote debugging of PHP programs.
import hashlib
import socket
import sys

if __name__ == '__main__':
    if len(sys.argv) == 1:
        raise Exception("Site name required")

    buffer_size = 1024
    ip = '127.0.0.1'

    # make an md5 of the site name (passed in on the CLI) and convert the first six hex characters
    # to an integer take the resulting int and compute a port number for this script
    # and core/framework/Internal/Utils/DevelopmentLogger.php to agree on
    port = (int(hashlib.md5(sys.argv[1]).hexdigest()[:6], 16) % 16383) + 49153

    addr = (ip, port)
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(addr)
    s.listen(5)

    print "Listening to site " + sys.argv[1] + " on port: " + str(port)

    try:
        while 1:
            client, addr = s.accept()
            tmp = client.recv(buffer_size)
            while tmp:
                print tmp
                tmp = client.recv(buffer_size)
            client.close()
    except:
        s.close()
        print "Exit"
