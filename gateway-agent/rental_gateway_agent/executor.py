import fcntl
import os
import subprocess


class FileLock:
    def __init__(self, path):
        self.path = path
        self.handle = None

    def __enter__(self):
        os.makedirs(os.path.dirname(self.path) or ".", exist_ok=True)
        self.handle = open(self.path, "a+")
        fcntl.flock(self.handle.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
        return self

    def __exit__(self, exc_type, exc, tb):
        if self.handle:
            fcntl.flock(self.handle.fileno(), fcntl.LOCK_UN)
            self.handle.close()


class ApacheExecutor:
    def __init__(self, config):
        self.config = config

    def configtest(self):
        if self.config.SKIP_CONFIGTEST:
            return True, "skipped"
        proc = subprocess.run(
            [self.config.APACHE_CTL, "configtest"],
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            universal_newlines=True,
        )
        output = proc.stdout or ""
        return proc.returncode == 0, output.strip()

    def graceful(self):
        if self.config.SKIP_APACHE_RELOAD:
            return True, "reload skipped"
        proc = subprocess.run(
            [self.config.APACHE_CTL, "graceful"],
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            universal_newlines=True,
        )
        output = proc.stdout or ""
        return proc.returncode == 0, output.strip()
