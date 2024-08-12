#!/nfs/project/aarnone/python26/install/bin/python

import subprocess

"""
General helper utilities
"""

"""
Runs the specified command in the optional 'cwd' directory
@param string|list The comand to run
@returns tuple A tuple containing the return code and output.
"""
def runCommand(command, cwd=None):
    proc = subprocess.Popen(command, cwd=cwd,
        shell=(False if command.__class__ == list else True),
        stdout=subprocess.PIPE, stderr=subprocess.STDOUT)

    output = proc.communicate()[0]
    returnCode = proc.returncode or 0
    return (returnCode, output)


"""
Runs a list of commands, where commands is a list of tuples having:
  [(
    {command}  string|list  The command to run
    {cwd}      string|None  The current working directory within which to run the command.
  ),..]

If a command returns a non zero return code, execution stops.

@param array commands The list of commands to run
@returns list A list of tuples containing (command, cwd, returnCode, output)
"""
def runCommands(commands):
    results = []
    for command, cwd in commands:
        returnCode, output = runCommand(command, cwd)
        results.append((command, cwd, returnCode, output))
        if returnCode != 0:
            break

    return results
