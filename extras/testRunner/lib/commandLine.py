import time
import platform
import sys


class CommandLine:
    """Prints messages to stdout with colors and timestamps"""

    startTime = time.time()
    hasWarnedEncodingErrors = False

    RED = '\033[91m'
    GREEN = '\033[92m'
    STOP = '\033[0m'

    @staticmethod
    def ask(prompt):
        return raw_input(prompt + " (y/n) ")

    @staticmethod
    def sayIndent(message):
        CommandLine.__protectedPrint('    ' + message)

    @staticmethod
    def say(message, messageType='info'):
        elapsedTime = time.time() - CommandLine.startTime

        CommandLine.__protectedPrint(u'[{0} @ {1:.2f}]: {2}'.format(messageType, elapsedTime, message))

    @staticmethod
    def green(message):
        CommandLine.__print(message, CommandLine.GREEN)

    @staticmethod
    def red(message):
        CommandLine.__print(message, CommandLine.RED)

    @staticmethod
    def puts(message):
        CommandLine.__protectedPrint(message)

    @staticmethod
    def putsIndent(message):
        CommandLine.__protectedPrint('    ' + message)

    @staticmethod
    def __print(message, code):
        CommandLine.__protectedPrint(u'{1}{0}{2}'.format(message, code, CommandLine.STOP))

    @staticmethod
    def __protectedPrint(message):
        # Terminal emulator doesn't support unicode. Swap unicode characters out with question marks.
        if platform.system() == 'Windows' or sys.stdout.encoding is None:
            if not CommandLine.hasWarnedEncodingErrors:
                CommandLine.hasWarnedEncodingErrors = True
                CommandLine.say("Your terminal emulator doesn't support unicode rendering. Replaced characters with question marks.")

            message = message.encode('ascii', 'replace')

        print(message)
        # force flush in case process times out
        sys.stdout.flush()
