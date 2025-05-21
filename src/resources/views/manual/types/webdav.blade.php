<h4><a name="webdav"></a>WebDAV</h4>

<p>
    WebDAV is a protocol supported by many web-based file management services. A WebDAV storage disk can connect to one of these services.
</p>

<p>
    In order to enable all features of BIIGLE, the WebDAV server should be configured for cross-origin resource sharing (CORS). Take a look at the <a href="{{route('manual-tutorials', ['volumes', 'remote-locations'])}}#cors">remote locations</a> article for more information on CORS and how it must be configured for BIIGLE.
</p>

<p>
    A WebDAV storage disk has the following options:
</p>

<dl>
    <dt>Base URI</dt>
    <dd>
        <p>
            This is the URI/URL of the WebDAV server.
        </p>
    </dd>

    <dt>Username</dt>
    <dd>
        <p>
            The username to use for authentication. If the WebDAV server allows public access, this option can be left empty.
        </p>
    </dd>

    <dt>Password</dt>
    <dd>
        <p>
            The password to use for authentication. If no username is required, the password can be left empty, too.
        </p>
    </dd>
</dl>
