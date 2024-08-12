class TestException(Exception):
    pass


class TestResult(object):
    def __init__(self, url=None, tap=None, errorMessage=None, additionalInformation=None):
        self.tap = tap
        self.url = url
        self.error = errorMessage
        self.additionalInformation = additionalInformation

    def isFailure(self):
        return not self.tap or self.error


class JsonTestResult(object):
    def __init__(self, json=None):
        self.json = json

    def isFailure(self):
        return not self.json


class TestRunner(object):
    pass
