<h4><a name="aos"></a>Aruna Object Storage</h4>

<p>
    The <a href="https://aruna-storage.org/">Aruna Object Storage</a> (AOS) is a storage service for the German initiative for a national research data infrastructure (<a href="https://www.nfdi.de/">NFDI</a>). Before you can start using AOS, you have to sign up for a user account on the <a href="https://aruna-storage.org/">website</a>.
</p>

<p>
    While the connection to AOS can be established via the same S3 protocol that is described above, the setup and configuration works a little differently. Here is a description of the S3 options for AOS:
</p>

<dl>
    <dt>Bucket name</dt>
    <dd>
        <p>
            The name of your AOS project.
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
            The "AccessKey" that is provided with new data proxy credentials.
        </p>
    </dd>

    <dt>Secret key</dt>
    <dd>
        <p>
            The "SecretKey" that is provided with new data proxy credentials.
        </p>
    </dd>
</dl>

<p>
    Detailed setup instructions:
</p>

<ol>
    <li>
        <p>
            Log in to the <a href="https://aruna-storage.org">AOS dashboard</a>, select "Explore" and then "Resources" in the menu at the top.
        </p>
    </li>
    <li>
        <p>
            Click on the "Create new" button and create a new project (we call it "myproject" here). The project name is the value of the <strong>Bucket name</strong> field that is required to create the new storage disk. With the project name you can also fill the <strong>Endpoint</strong> field.
        </p>
    </li>
    <li>
        <p>
            Now select "Access" and then "Data proxies" in the AOS menu at the top. Choose a data proxy where you would like to store your data. There, click on the "Create Credential" button. The AccessKey is the value of the <strong>Access key</strong> field and the SecretKey is the value of the <strong>Secret key</strong> field that is required to create the new storage disk.
        </p>
    </li>
</ol>

<p>
    Now you have the values for all fields that are required to create the new storage disk. However, one more step is required before you can annotate your data without restrictions in BIIGLE. You have to configure "Cross-origin resource sharing (CORS)". This is done as follows:
</p>

<ol>
    <li>
        <p>
            Install <code>s3cmd</code> and run <code>s3cmd --configure</code>. Enter the access key and secret key from above. Don't change the default region. Enter the S3 endpoint <code>data.gi.aruna-storage.org</code> and the bucket template <code>%(bucket)s.data.gi.aruna-storage.org</code>. Leave the remaining options unchanged. Don't run the test with the supplied credentials and save the settings.
        </p>
    </li>
    <li>
        <p>
            Create a file called <code>cors.xml</code> with the following content:
        </p>
<pre>
&lt;CORSConfiguration&gt;
  &lt;CORSRule&gt;
    &lt;AllowedOrigin&gt;{{url('/')}}&lt;/AllowedOrigin&gt;
    &lt;AllowedMethod&gt;GET&lt;/AllowedMethod&gt;
    &lt;AllowedHeader&gt;*&lt;/AllowedHeader&gt;
    &lt;MaxAgeSeconds&gt;30&lt;/MaxAgeSeconds&gt;
  &lt;/CORSRule&gt;
&lt;/CORSConfiguration&gt;
</pre>
        <p>
            Then run the following command: <code>s3cmd setcors cors.xml s3://myproject</code> (you should replace "myproject" with the actual name of your project). That's it. Now CORS is configured for your project.
        </p>
    </li>
</ol>

<p>
    Here is a brief example for how you can upload files to your project. This is also done with <code>s3cmd</code>;
</p>

<ol>
    <li>
        <p>
            Make sure <code>s3cmd</code> is configured as described above.
        </p>
    </li>
    <li>
        <p>
            Now navigate to the parent of the directory that you want to upload. Upload the whole directory with the following command (replace "mydir" with the name of the directory to upload and "myproject" with the name of your project):
        </p>
        <pre>s3cmd put -r mydir s3://myproject/</pre>
        <p>
            The directory will be created as a new dataset as part of your AOS project. In BIIGLE, you will see it as a directory in the file browser.
        </p>
    </li>
</ol>

