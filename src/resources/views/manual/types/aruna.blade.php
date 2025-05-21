<h4><a name="aruna"></a>Aruna</h4>

<p>
    <a href="https://aruna-engine.org/">Aruna</a> is a storage service for the German initiative for a national research data infrastructure (<a href="https://www.nfdi.de/">NFDI</a>). Before you can start using Aruna, you have to sign up for a user account on the <a href="https://aruna-engine.org/">website</a>.
</p>

<p>
    The connection to Aruna is established via the S3 protocol with the following options:
</p>

<dl>
    <dt>Project name</dt>
    <dd>
        <p>
            The name of your Aruna project.
        </p>
    </dd>

    <dt>Endpoint</dt>
    <dd>
        <p>
            The endpoint is the project name combined with the data proxy URL. In the Aruna web interface, navigate to your account settings and the "Data Proxies" tab. There select "Create Credentials" if you do this for the first time or "Get Credentials" if you already have credentials. The data proxy URL looks something like this: <code>https://data.gi.aruna-storage.org</code>. To get the endpoint of the storage disk, append the project name like this: <code>https://MyProject.data.gi.aruna-storage.org</code>.
        </p>
    </dd>

    <dt>Access key</dt>
    <dd>
        <p>
            The "Access Key ID" that is provided with the data proxy credentials.
        </p>
    </dd>

    <dt>Secret key</dt>
    <dd>
        <p>
            The "Access Secret" that is provided with the data proxy credentials.
        </p>
    </dd>
</dl>

<p>
    Detailed setup instructions:
</p>

<ol>
    <li>
        <p>
            Log in to the <a href="https://aruna-engine.org">Aruna dashboard</a> then select "Resources" in the menu at the top.
        </p>
    </li>
    <li>
        <p>
            Click on the "Create new resource" button and create a new project (we call it "myproject" here). The project name is the value of the <strong>Project name</strong> field that is required to create the new storage disk. With the project name you can also fill the <strong>Endpoint</strong> field.
        </p>
    </li>
    <li>
        <p>
            Get the access key and secret key as described above.
        </p>
    </li>
</ol>

<p>
    Now you have the values for all fields that are required to create the new storage disk. However, one more step is required before you can annotate your data without restrictions in BIIGLE. You have to configure "Cross-origin resource sharing (CORS)". This is done as follows:
</p>

<ol>
    <li>
        <p>
            Install <code>s3cmd</code> and run <code>s3cmd --configure</code>. Enter the access key and secret key from above. Don't change the default region. Enter the S3 endpoint of your data proxy (e.g. <code>data.gi.aruna-storage.org</code>) and the bucket template (e.g. <code>%(bucket)s.data.gi.aruna-storage.org</code>). Leave the remaining options unchanged. Don't run the test with the supplied credentials and save the settings.
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
            The directory will be created as a new dataset as part of your Aruna project. In BIIGLE, you will see it as a directory in the file browser.
        </p>
    </li>
</ol>

