<h4><a href="aos"></a>Aruna Object Storage</h4>

<p>
    The <a href="https://aruna-storage.org/">Aruna Object Storage</a> (AOS) is a storage service for the German initiative for a national research data infrastructure (<a href="https://www.nfdi.de">NFDI</a>). Before you can start using AOS, you have to sign up for a user account on the <a href="https://aruna-storage.org/">website</a>.
</p>

<p>
    An AOS storage disk has the following options:
</p>

<dl>
    <dt>Collection ID</dt>
    <dd>
        <p>
            The ID of the AOS collection as returned by the API.
        </p>
    </dd>

    <dt>Bucket name</dt>
    <dd>
        <p>
            The name of the collection bucket which consists of the collection version (or "latest"), the collection name and the project name joined with dots.
        </p>
    </dd>

    <dt>Endpoint</dt>
    <dd>
        <p>
            The endpoint is the URL <code>https://&lt;bucket&gt;.data.gi.aruna-storage.org</code> where <code>&lt;bucket&gt;</code> is replaced with the bucket name above.
        </p>
    </dd>

    <dt>Access key</dt>
    <dd>
        <p>
            The "S3 access key" that is provided with a new access token.
        </p>
    </dd>

    <dt>Secret key</dt>
    <dd>
        <p>
            The "S3 secret key" that is provided with a new access token.
        </p>
    </dd>

    <dt>API secret</dt>
    <dd>
        <p>
            The "secret" that is provided with a new access token.
        </p>
    </dd>
</dl>

<p>
    Detailed setup instructions:
</p>

<ol>
    <li>
        <p>
            Log in to the <a href="https://aruna-storage.org/panel/projects">AOS dashboard</a> and note down the ID (ULID) of your project.
        </p>
    </li>
    <li>
        <p>
            <a href="https://aruna-storage.org/panel/tokens">Create a new token</a> with the "project" token type, choose a name, enter the project ULID, choose "MODIFY" permissions and click "Create". The note down the "secret".
        </p>
    </li>
    <li>
        <p>
            Create a <a href="https://arunastorage.github.io/Documentation/v1.0.x/get_started/basic_usage/04_How-To-Collections/">new collection</a> in the project with the following cURL request to the API (choose your own collection <code>name</code> and <code>description</code> and replace <code>PROJECT_ULID</code> with the project ID and <code>TOKEN</code> with the token secret):
        </p>
<pre>
curl -d '
  {
    "name": "mycollection",
    "description": "This is my collection.",
    "projectId": "PROJECT_ULID",
    "dataclass": "DATA_CLASS_PRIVATE"
  }' \
  -H 'Authorization: Bearer TOKEN' \
  -H 'Content-Type: application/json' \
  -X POST https://api.aruna-storage.org/v1/collection
</pre>
        <p>
            The returned <code>collectionID</code> is the value of the <strong>Collection ID</strong> field that is required to create a new AOS storage disk.
        </p>
        <p>
            The value of the <strong>Bucket name</strong> field can be determined now, too. It consists of the collection version (or "latest"), the collection name and the project name joined with dots (example: <code>latest.mycollection.myproject</code>).
        </p>
        <p>
            Finally, the value of the <strong>Endpoint</strong> field can be determined. It is the URL <code>https://&lt;bucket&gt;.data.gi.aruna-storage.org</code> where <code>&lt;bucket&gt;</code> is replaced with the bucket name above.
        </p>
    </li>
    <li>
        <p>
            Now go back to the <a href="https://aruna-storage.org/panel/tokens">dashboard</a> and create a new token. This time it should have the token type "collection". Enter the collection ULID and choose "READ" permissions. This token provides the "S3 access key" as the value of the <strong>Access key</strong> field, the "S3 secret key" as the value of the <strong>Secret key</strong> field and the "Secret" as the value of the <strong>API secret</strong> field that are required to create a new AOS storage disk.
        </p>
    </li>
</ol>

<p>
    Now you have the values for all fields that are required to create a new AOS storage disk. Here is a brief example for how you can upload files to your collection using the S3 protocol in a command line:
</p>

<ol>
    <li>
        <p>
            Go to the AOS <a href="https://aruna-storage.org/panel/tokens">dashboard</a> and create a new token. It should have the token type "collection". Enter the collection ULID and choose "MODIFY" permissions. Note down the "S3 access key" and "S3 secret key".
        </p>
    </li>
    <li>
        <p>
            Install `s3cmd` and run `s3cmd --configure`. Enter the access key and secret key. Don't change the default region. Enter the S3 endpoint <code>data.gi.aruna-storage.org</code> and the bucket template <code>%(bucket)s.data.gi.aruna-storage.org</code>. Leave the remaining options unchanged. Don't run the test with the supplied credentials and save the settings.
        </p>
    </li>
    <li>
        <p>
            Now navigate to the directory of files that you want to upload. Currently, no subdirectories can be uploaded via S3. Upload all files of the current directory with the command (replace <code>mycollection</code> and <code>myproject</code> with the names of your AOS collection and project, respectively):
        </p>
        <pre>s3cmd put * s3://latest.mycollection.myproject/</pre>
    </li>
</ol>
